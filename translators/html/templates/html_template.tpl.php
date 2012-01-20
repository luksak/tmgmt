<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="de-CH" lang="de-CH" xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="JobID"    content="<?=$tjid?>" />
    <meta name="languageSource" content="<?=$source_language?>" />
    <meta name="languageTarget" content="<?=$target_language?>" />

    <title>Job ID <?=$tjid?></title>
  </head>
  <body>
    <?php foreach($items as $item_key => $item):?>
    <div class="asset" id="<?=$item_key?>">
        <?php foreach($item as $field_key => $field):?>
        <div class="atom" id="<?=$field_key?>"><?=$field['#text']?></div>
        <?php endforeach;?>
    </div>
    <?php endforeach;?>
  </body>
</html>