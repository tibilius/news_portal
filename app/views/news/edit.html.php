<?= $this->form->create(); ?>
<?= $this->form->field($key, array('type' => 'hidden', 'value' => $news->{$key})) ?>
<?= $this->form->field('title', array('value' => $news->title)); ?>
<?= $this->form->field('body', array('type' => 'textarea', 'value' => $news->body)); ?>
<?= $this->form->submit('Save News'); ?>
<?= $this->form->end(); ?>
<?php if ($success): ?>
    <p>News Successfully saved</p>
<?php endif; ?>
