<?php


if ($_SERVER['HTTP_HOST'] != 'msc') {
    echo json_encode($_SERVER);
    return;
}

$files = [];
$a = [...glob('*.php'), ...glob('tpl/*.php'), ...glob('tpl/*.htm')];
foreach ($a as $k => $v) {
    $content = file_get_contents($v);

    $content = str_replace('<?=json_encode($pageProps)?>', '', $content);
    preg_match_all('~<\?.*?\?>~is', $content . '?>', $b);
    $lengthPhp = 0;
    foreach ($b[0] as $r) {
        $lengthPhp += mb_strlen($r);
    }

    preg_match_all('~<script?.*?/script>~is', $content, $b);
    $lengthScripts = 0;
    foreach ($b[0] as $r) {
        $lengthScripts += mb_strlen($r);
    }

    $phpSize = $lengthPhp;
    $style = '';
    if (strpos($v, 'tpl') === 0) {
        if ($phpSize > 100) {
            $style = 'color:red';
        }
    } elseif ($phpSize > 5000) {
        $style = 'color:red';
    } elseif ($phpSize < 1000) {
        $style = 'color:#aaa';
    }
    if ($style) {
        $phpSize = '<span style="' . $style . '">' . $phpSize . '</span>';
    }

    $extra = '';
    if (strpos($content, 'if (isajax()) {')) {
        $extra = 'if isajax';
    }
    if (strpos($content, '...pageProps')) {
        $extra = '...pageProps';
    }
    if (strpos($content, '...options')) {
        $extra = '...options';
    }

    $length = mb_strlen($content);
    $files [] = [
        'file' => $v,
        'php' => round($lengthPhp / $length * 100, 0),
        'phpSize' => $phpSize,
        'scripts' => round($lengthScripts / $length * 100, 0),
        'html' => round(($length - $lengthPhp - $lengthScripts) / $length * 100, 0),
        'extra' => $extra
    ];
}

echo printTable($files, [
    'htmlspecialchars' => 0,
    'class' => 'tt',
    'headers' => 1,
    'callbackValue' => function ($header, $value) {
        /*if ($header == 'login') {
            $value = '<a href="?page=logs&login='.$value.'">'.$value.'</a>';
        }*/
        return $value;
    }
]);
