<?php
function sanitize($d){ return htmlspecialchars(strip_tags(trim($d))); }
function timeAgo($datetime){
    $t = time()-strtotime($datetime);
    if($t<60) return 'just now';
    if($t<3600) return floor($t/60).'m ago';
    if($t<86400) return floor($t/3600).'h ago';
    return floor($t/86400).'d ago';
}
function stars($r){ $o=''; for($i=1;$i<=5;$i++) $o.=$i<=$r?'⭐':'☆'; return $o; }
function urgencyBadge($u){
    return match($u){
        'urgent'=>'<span class="badge badge-danger">🔴 Urgent</span>',
        'within_week'=>'<span class="badge badge-warning">🟡 This Week</span>',
        default=>'<span class="badge badge-success">🟢 Flexible</span>'
    };
}
function tradeIcon($t){
    return match($t){
        'Plumbing'=>'🔧','Electrical'=>'⚡','Painting'=>'🖌️',
        'Carpentry'=>'🪚','Tiling'=>'🏠','Roofing'=>'🏗️',
        'Welding'=>'🔩','Landscaping'=>'🌿',default=>'🔨'
    };
}
$TRADES=['Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman'];
?>
