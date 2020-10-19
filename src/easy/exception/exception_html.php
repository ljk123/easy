<h1><?=get_class($e)?>::<?=$e->getMessage()?></h1>
<span>File:<?=$e->getFile()?>:<?=$e->getLine()?></span>
<div class="code">
    <ul>
        <?php
        $i_line=$script['first'];
        foreach ($script['source'] as $source){
            ?>
            <li class="<?=$i_line==$e->getLine()?'error':''?> line"><pre><?=$source?></pre></li>
            <?php
            $i_line++;
        }?>
    </ul>
</div>
<div class="trace">
    <ul>
        <?php
        $trace_string=$e->getTraceAsString();
        $traces=explode(PHP_EOL,$trace_string);
        foreach ($traces as $trace){
            ?>
            <li><?=$trace;?></li>
            <?php
            $i_line++;
        }?>
    </ul>
</div>
<div class="footer"> xxxxx copyright </div>
<style>
    *{
        margin: 0;
        padding: 0;
    }
    ul,li{
        list-style: none;
    }
    .code{
        background-color: #2b2b2b;
    }
    .code li{
        color: #e8bf6a;
    }

    .code .error{
        background-color: #3a2323;
    }
</style>