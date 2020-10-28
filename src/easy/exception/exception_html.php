<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta charset="UTF-8">
    <title>未捕获的异常</title>
    <meta name="robots" content="noindex,nofollow"/>
    <link href="https://cdn.bootcdn.net/ajax/libs/prism/1.22.0/themes/prism.min.css" rel="stylesheet">
</head>
<div class="exception">
    <h1> 未捕获的异常 <?= get_class($e) ?>::<?= $e->getMessage() ?></h1>
    <span>File:<?= $e->getFile() ?>:<?= $e->getLine() ?></span>
    <div class="code">
        <ul>
            <?php
            $i_line = $script['first'];
            foreach ($script['source'] as $source) {
                ?>
                <li class="<?= $i_line == $e->getLine() ? 'error' : '' ?> line">
                    <pre><span><?= $i_line ?>.</span><?= $source ?></pre>
                </li>
                <?php
                $i_line++;
            } ?>
        </ul>
    </div>
    <div class="trace">
        <ul>
            <?php
            $trace_string = $e->getTraceAsString();
            $traces = explode(PHP_EOL, $trace_string);
            foreach ($traces as $trace) {
                ?>
                <li><?= $trace; ?></li>
                <?php
                $i_line++;
            } ?>
        </ul>
    </div>
    <pre><code class="language-javascript line-numbers"><?php
            $i_line = $script['first'];
            foreach ($script['source'] as $source) {
                echo $source;
                $i_line++;
            } ?></code></pre>
</div>
<div class="footer"><a href="https://www.easy-php.cn">easyPHP</a> Copyright&copy;<?= date('Y') ?>
</div>
<style>
    * {
        margin: 0;
        padding: 0;
    }

    ul, li {
        list-style: none;
    }

    a {
        color: #000;
        text-decoration: none;
    }

    .exception {
        margin: 20px;
        padding: 20px;
        border: 1px solid #ddd;
        border-bottom: 0 none;
    }

    .code {
        background-color: #252525;
    }

    .code li {
        color: #e8bf6a;
    }

    .code li pre span {
        background-color: #313335;
        color: #414346;
        padding-left: 10px;
    }

    .code .error pre span {
        color: #888;
    }

    .code .error {
        background-color: #323232;
    }

    .footer {
        text-align: center;
    }
</style>
<script src="https://cdn.bootcdn.net/ajax/libs/prism/1.22.0/prism.min.js"></script>
</html>