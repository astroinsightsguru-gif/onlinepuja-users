<?php
$file = '/home/onlinepujalive/htdocs/onlinepuja.live/resources/views/frontend/pages/puja-details.blade.php';
$content = file_get_contents($file);

$needle = '<!-- FAQs -->';
$replacement = "@include('frontend.layout.puja-consecrated-products-mesh')\n\n            <!-- FAQs -->";

if (strpos($content, 'puja-consecrated-products-mesh') === false) {
    if (strpos($content, $needle) !== false) {
        $content = str_replace($needle, $replacement, $content);
        file_put_contents($file, $content);
        echo "✓ Injected puja-consecrated-products-mesh into puja-details.blade.php\n";
    } else {
        echo "! Could not find needle <!-- FAQs --> in puja-details.blade.php\n";
    }
} else {
    echo "• puja-consecrated-products-mesh already present in puja-details.blade.php\n";
}
