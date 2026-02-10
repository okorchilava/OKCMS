<?php
/**
 * Dynamic Sidebar Template
 * ყველა ლოგიკა გადატანილია ok_dynamic_sidebar() ფუნქციაში.
 */
?>

<aside class="sidebar-wrapper" style="position: sticky; top: 2rem;">
    <?php 
    // ვიძახებთ 'sidebar-main' არეალს
    // ეს ფუნქცია ავტომატურად დახატავს ყველა აქტიურ ვიჯეტს იმ თანმიმდევრობით, რაც ადმინკაშია
    if (function_exists('ok_dynamic_sidebar')) {
        ok_dynamic_sidebar('sidebar-main'); 
    }
    ?>
</aside>

<style>
    .hover-highlight:hover { background-color: #f8f9fa; }
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .object-fit-cover { object-fit: cover; }
    .hover-bg:hover { background-color: #f1f3f5; color: #0d6efd !important; }
    .transition { transition: all 0.2s ease; }
    
    /* ვიჯეტის ბოქსის სტილი */
    .widget-box { overflow: hidden; }
</style>