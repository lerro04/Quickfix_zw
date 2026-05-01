<?php
require_once __DIR__.'/config.php';
const PLATFORM_COMMISSION_RATE = 0.10;

function sanitize($d){ return htmlspecialchars(strip_tags(trim($d))); }
function timeAgo($datetime){
    $t = time() - strtotime($datetime);
    if($t < 60) return 'just now';
    if($t < 3600) return floor($t / 60).'m ago';
    if($t < 86400) return floor($t / 3600).'h ago';
    return floor($t / 86400).'d ago';
}
function icon($name, $label=''){
    $labelAttr = $label !== '' ? ' aria-label="'.htmlspecialchars($label, ENT_QUOTES).'"' : ' aria-hidden="true"';
    return '<i class="fa-solid fa-'.$name.'"'.$labelAttr.'></i>';
}
function stars($r){
    $o = '';
    for($i = 1; $i <= 5; $i++){
        $o .= $i <= $r ? '<i class="fa-solid fa-star" aria-hidden="true"></i>' : '<i class="fa-regular fa-star" aria-hidden="true"></i>';
    }
    return '<span class="star-rating" aria-label="Rating '.number_format((float)$r, 1).' out of 5">'.$o.'</span>';
}
function urgencyBadge($u){
    return match($u){
        'urgent' => '<span class="badge badge-danger">'.icon('circle-exclamation').' Urgent</span>',
        'within_week' => '<span class="badge badge-warning">'.icon('calendar-week').' This Week</span>',
        default => '<span class="badge badge-success">'.icon('calendar-check').' Flexible</span>'
    };
}
function tradeIcon($t){
    return match($t){
        'Plumbing' => icon('wrench'),
        'Electrical' => icon('bolt'),
        'Painting' => icon('paint-roller'),
        'Carpentry' => icon('hammer'),
        'Tiling' => icon('border-all'),
        'Roofing' => icon('house'),
        'Welding' => icon('fire-flame-curved'),
        'Landscaping' => icon('leaf'),
        default => icon('screwdriver-wrench')
    };
}
function paginationData($totalItems, $perPage, $currentPage){
    $totalPages = max(1, (int)ceil($totalItems / max(1, $perPage)));
    $page = min(max(1, $currentPage), $totalPages);
    return [
        'page' => $page,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage
    ];
}
function pageUrl($page){
    $params = $_GET;
    $params['page'] = $page;
    return htmlspecialchars($_SERVER['PHP_SELF'].'?'.http_build_query($params));
}
function renderPagination($page, $totalPages){
    if($totalPages <= 1) return '';
    $html = '<div class="pagination" aria-label="Pagination">';
    if($page > 1){
        $html .= '<a class="pagination-link" href="'.pageUrl($page - 1).'">'.icon('chevron-left').' Previous</a>';
    }
    for($i = 1; $i <= $totalPages; $i++){
        $class = $i === $page ? 'pagination-link active' : 'pagination-link';
        $html .= '<a class="'.$class.'" href="'.pageUrl($i).'">'.$i.'</a>';
    }
    if($page < $totalPages){
        $html .= '<a class="pagination-link" href="'.pageUrl($page + 1).'">Next '.icon('chevron-right').'</a>';
    }
    $html .= '</div>';
    return $html;
}

$TRADES = ['Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman'];
?>
