
<?=$this->form->create(); ?>
    <?=$this->form->field('title');?>
    <?=$this->form->field('body', array('type' => 'textarea'));?>
    <?=$this->form->submit('Add News'); ?>
<?=$this->form->end(); ?>
<?php if ($success): ?>
    <p>News Successfully Saved</p>
<?php endif; ?>
<?php echo $this->html->link('News list','/news')?>