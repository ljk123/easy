<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta charset="UTF-8">
    <title>未捕获的异常</title>
    <meta name="robots" content="noindex,nofollow"/>
    <link rel="stylesheet"
          href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.4.0/styles/default.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/10.4.0/highlight.min.js"></script>
</head>
<div class="exception">
    <h1> 未捕获的异常 <?= get_class($e) ?>::<?= $e->getMessage() ?></h1>
    <span>File:<?= $e->getFile() ?>:<?= $e->getLine() ?></span>
    <pre><code class="language-php"><?php
            $i_line = $script['first'];
            foreach ($script['source'] as $source) {
                echo '<text class="' . ($i_line == $e->getLine() ? 'error' : '') . ' ">' . $i_line . '.' . $source . '</text>';
                $i_line++;
            } ?></code></pre>
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

    .error {
        background-color: rgba(58, 27, 4, 0.72);
    }

    .footer {
        text-align: center;
    }
</style>
<style>
</style>
<script>hljs.initHighlightingOnLoad();</script>
<script type="text/javascript">

</script>

</html>