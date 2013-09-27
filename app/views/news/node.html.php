<h1><?=$news->title?></h1>
<p><?php echo htmlspecialchars(addslashes($news->body))?></p>
<p>Created : <?=date($dateFormat, $news->ctime);?></p>
<p>Modified : <?=date($dateFormat, $news->mtime);?></p>

<ul class='menu'>
  <li><?=$this->html->link('Back to newslist','/news')?></li>
  <li><?=$this->html->link('Edit that news','/news/edit/'. urlencode($news->{$key}))?></li>
</ul>


