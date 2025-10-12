<?php

if(JBShared::hCaptchaCheck()){
  include "balfcomment.php";

  $b = new BalfComment(CreateDatabaseConnection('comments'));

  $url = $_POST['url'];
  $title = $_POST['title'];
  $poster_name = $_POST['disp_name'];
  $text = nl2br($_POST['content']);

  $parent = $_POST['parent'];

  $user_id = -1;
  if(isLoggedIn()){
    $user_id = GetUser()['user_id'];
  }

  $b->newComment($url, $title, $text, $poster_name, $parent, $user_id);



  header("Location: /assets/comments/?url=".$url);
  exit;
} else{
  echo 'Failure';
}


?>
