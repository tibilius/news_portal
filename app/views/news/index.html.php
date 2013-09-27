<h1><?=$title?></h1>
<?php if (count($news)): ?>
<table>
    <tr><td>Title</td><td>created</td><td>edited</td></tr>
    <?php foreach($news as $node): ?>
    <tr>
      <td><?=$this->html->link($node->title, 'node/' . urlencode($node->{$key}))?></td>
        <td><?=date($dateFormat, $node->ctime) ?></td>
        <td><?=date($dateFormat, $node->mtime) ?></td>
        <td><?=$this->html->link('edit', 'edit/' .  urlencode($node->{$key})) ?></td>
        <td><?=$this->html->link('delete', 'delete/' .  urlencode($node->{$key})) ?></td>
    </td>
    <?php endforeach; ?>
</table>
<?php endif;?>
<p></p>
<div class='add_link'><?=$this->html->link('Add News','add')?></div>