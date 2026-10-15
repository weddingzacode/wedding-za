<?php
declare(strict_types=1);
/**
 * Resolves original image-archive paths, including common cPanel nested ZIP extraction.
 * Returns browser-relative paths for files that physically exist on this server only.
 */
function wz_venue_photo_url(string $source): ?string {
    static $cache=[];
    $source=ltrim(str_replace('\\', '/', $source), '/');
    if(array_key_exists($source,$cache))return $cache[$source];
    if(!preg_match('~^assets/images/venues/collection-photos/[a-z0-9-]+/[0-9]{3}\.(?:webp|jpg|jpeg|png)$~i',$source))
        return $cache[$source]=null;
    $root=dirname(__DIR__).'/';
    foreach(['','UPLOAD/','assets/','assets/images/','assets/images/venues/','assets/images/venues/collection-photos/','assets/images/venues/collection-photos/UPLOAD/','Weddingza-Venue-221-Photos-For-cPanel/'] as $prefix) {
        $relative=$prefix.$source;
        if(is_file($root.$relative))return $cache[$source]=$relative;
    }
    return $cache[$source]=null;
}
function wz_venue_photo_records(): array {
    static $records=null;
    if($records!==null)return $records;
    $file=__DIR__.'/../assets/data/venue-photos.json';
    $all=is_file($file)?json_decode((string)file_get_contents($file),true):null;
    $records=[];
    foreach(is_array($all)?$all:[] as $entry) {
        if(!is_array($entry)||empty($entry['id'])||empty($entry['name'])||!is_array($entry['images']??null))continue;
        $resolved=[];
        foreach($entry['images'] as $photo) {
            $url=is_string($photo)?wz_venue_photo_url($photo):null;
            if($url!==null&&!in_array($url,$resolved,true))$resolved[]=$url;
        }
        if(!$resolved)continue;
        $preferred=wz_venue_photo_url((string)($entry['image']??''));
        $entry['image']=$preferred??$resolved[0];
        $entry['images']=$resolved;
        $entry['aliases']=is_array($entry['aliases']??null)?$entry['aliases']:[];
        $records[]=$entry;
    }
    return $records;
}
function wz_venue_photo_key(string $name):string {
    return strtolower((string)preg_replace('/[^a-z0-9]/i','',$name));
}
function wz_venue_photo_match(array $venue,array $photo):bool {
    if((string)($venue['id']??'')===$photo['id'])return true;
    $name=wz_venue_photo_key((string)($venue['name']??''));
    foreach(array_merge([$photo['name']],$photo['aliases']) as $alias)
        if($name===wz_venue_photo_key($alias))return true;
    return false;
}
function wz_apply_venue_photos(array $venues):array {
    foreach($venues as &$venue){
        if(($venue['category']??'')!=='Venues'&&($venue['business_type']??'')!=='venue')continue;
        foreach(wz_venue_photo_records() as $photo){
            if(!wz_venue_photo_match($venue,$photo))continue;
            $venue['image']=$photo['image'];
            $venue['images']=$photo['images'];
            $venue['photos']=count($photo['images']);
            $venue['illustrated']=false;
            break;
        }
    }
    unset($venue);
    return $venues;
}
function wz_venue_gallery(array $venue):void {
    $images=array_values(array_unique(array_filter($venue['images']??[])));
    if(!$images || count($images)<2)return;
    ?>
    <div class="vc-gallery" data-vc-gallery>
        <script type="application/json" data-vc-images><?= json_encode($images,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES) ?></script>
        <button class="vc-gallery-open" type="button" data-vc-gallery-open data-vc-name="<?= h($venue['name']) ?>">▧ <?= count($images) ?> photos ↗</button>
        <noscript><details><summary>View venue photos</summary><?php foreach($images as $img):?><a href="<?= h($img) ?>"><img src="<?= h($img) ?>" alt="<?= h($venue['name']) ?>" loading="lazy"></a><?php endforeach;?></details></noscript>
    </div><?php
}
function wz_venue_gallery_dialog():void { ?>
<dialog class="vc-photo-dialog" aria-labelledby="vc-photo-title">
<header><div><h2 id="vc-photo-title">Venue photos</h2><p data-vc-photo-count aria-live="polite"></p></div><button type="button" data-vc-close aria-label="Close photo gallery">×</button></header>
<div class="vc-photo-stage"><button type="button" data-vc-prev aria-label="Previous photo">‹</button><img data-vc-photo alt=""><button type="button" data-vc-next aria-label="Next photo">›</button></div>
<div class="vc-photo-thumbs" aria-label="Choose a photo"></div>
</dialog><?php }
