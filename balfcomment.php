<?php

class BalfComment {

  private $connection = null;

  public function createTables(){
    $query1 = 'CREATE TABLE `BalfComment_Posts` (`comment_id` int(11) NOT NULL, `comment_url` varchar(100) NOT NULL, `comment_title` varchar(50) NOT NULL, `comment_text` text NOT NULL, `poster_name` varchar(20) NOT NULL, `timestamp` int(11) NOT NULL, `parent` int(11) NOT NULL DEFAULT -1, `user_id` int(11) NOT NULL DEFAULT -1)';

    $query2 = 'ALTER TABLE `BalfComment_Posts` ADD PRIMARY KEY (`comment_id`);';

    $query3 = 'ALTER TABLE `BalfComment_Posts` MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;';
  }

  public function __construct($con){
    $this->connection = $con;
  }

  public function getComments($url){
    //Find parent comments first
    $query = "SELECT * FROM `BalfComment_Posts` WHERE comment_url = :url AND parent = -1 ORDER BY timestamp";
    $stmt = $this->connection->prepare($query);

    if(!$stmt->execute(array(":url" => $url))){
      return false;
    }

    //Now find replies
    $query = "SELECT * FROM `BalfComment_Posts` WHERE comment_url = :url AND parent > -1 ORDER BY timestamp";
    $reply_statement = $this->connection->prepare($query);

    //Iterate the comments
    $main_array = array();
    while($comment = $stmt->fetch(PDO::FETCH_ASSOC)){

      //Create an empty replies array
      $comment['replies'] = array();
      $main_array[$comment['comment_id']] = $comment;
    }

    if(!$reply_statement->execute(array(":url" => $url))){
      return false;
    }

    //Store the replies in the replies array
    while($comment = $reply_statement->fetch(PDO::FETCH_ASSOC)){
      array_push($main_array[$comment['parent']]['replies'], $comment);
    }

    return $main_array;

  }

  public function newComment($url, $title, $text, $poster_name, $parent = -1, $user_id = -1){
    $timestamp = time();
    $query = "INSERT INTO `BalfComment_Posts` (comment_url, comment_title, comment_text, poster_name, timestamp, parent, user_id) VALUES (:url, :title, :text, :poster, :timestamp, :parent, :user_id)";
    $stmt = $this->connection->prepare($query);

    if(!$stmt->execute(array(":url" => $url, ":title" => htmlentities($title), ":text" => htmlentities($text), ":poster" => htmlentities($poster_name), ":timestamp" => $timestamp, ":parent" => $parent, ":user_id" => $user_id))){
      return false;
    }

    return true;

  }

  private function autoGenerateComment($comments, $comment){
    $output = "";
    $reply = false;
    $reply_class = "";
    if($comment['parent'] != -1){
      $reply = true;
      $reply_class = " reply";

      if($comment['user_id'] != -1){
        $reply_class = " reply is_user";
      }
    }
    $output .= '<div class="balfcomment'.$reply_class.'" id="balfcomment_id_'.$comment['comment_id'].'" data-poster-name="'.$comment['poster_name'].'">';
      $output .= '<div class="wrapper">';
        $output .= '<div class="top">';
          $said = "";//" said";
          if($reply){
            $said = ' replied to <a href="#balfcomment_id_'.$comment['parent'].'">' . $comments[$comment['parent']]['poster_name'] . '</a>';
          }
          $user = "";
          if($comment['user_id'] != -1){
            $user = '<span class="user">User</span>';
            if($comment['user_id'] == 1){
              $user = '<span class="user">Site Admin</span>';
            }
          }
          //$output .= '<div class="col_lg_9 col_md_9 col_sm_8 col_xs_6 col_xxs_6">';
            $output .= '<div class="poster">'.$comment['poster_name'].$user. $said.'</div>';
          //$output .= '</div>';
          //$output .= '<div class="col_lg_3 col_md_3 col_sm_4 col_xs_6 col_xxs_6">';
            $output .= '<div class="timestamp">'.date("Y-m-d H:i:s", $comment['timestamp']).'</div>';
          //$output .= '</div>';
        $output .= '</div>';
        $output .= '<div class="content">';
          $output .= '<div class="title">'.$comment['comment_title'].'</div>';
          $output .= '<div class="text">'.html_entity_decode($comment['comment_text']).'</div>';
        $output .= '</div>';
      $output .= '<div class="reply_zone"><div class="right_align"><button class="bc_reply_button button" data-comment-id="'.$comment['comment_id'].'">Reply</button></div></div>';
      //$output .= '</div>';
      $last_poster = '';//$comment['poster_name'];
      $count = 0;
      $open = false;
      $print_name = false;

      $output .= '</div>';


    $output .= '</div>';

    return $output;
  }

  public function autoGenerateComments($url){
    $comments = $this->getComments($url);
    //print_r($comments);
    $output = '<div class="balfcomments">';
    if(count($comments) > 0){
      foreach($comments as $k => $comment){
        echo $this->autoGenerateComment($comments, $comment);

        foreach($comment['replies'] as $reply){
          echo $this->autoGenerateComment($comments, $reply);
        }
      }
    } else {
      $output .= '<p style="color:#999" class="center_align">There are no comments on this page.</p>';
    }

    $output .= '</div>';

    return $output;
  }


}


?>
