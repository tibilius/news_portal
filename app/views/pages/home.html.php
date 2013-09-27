<h1>Make you choise</h1>
<ul class='menu'>
  <li>&Lt;<li>
  <li><?php echo $this->html->link('Lithium framework setup', 'framework'); ?></li>
  <li class='delimeter'>&nbsp;</li>
  <li><?php echo $this->html->link('News', '/news'); ?></li>
  <li>&Gt;</li>
</ul>
<style>
  ul.menu li{
    float:left;
    margin: 0 10px;
    list-style: none;
  }
  ul.menu li.delimeter{
    padding-right: 50px;
  }
</style>


