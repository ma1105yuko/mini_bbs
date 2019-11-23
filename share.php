<?php
session_start();
require('dbconnect.php');


if (isset($_SESSION['id']) && isset($_REQUEST['ret'])) {
   
    //リツイートの重複を調べる
	$shares = $db->prepare('SELECT COUNT(*) AS cnt FROM share WHERE posts_id=? AND member_id=?');
    $shares->execute(array(
        $_REQUEST['ret'],
        $_SESSION['id']
    ));
    
    $share = $shares->fetch();
   
    if (empty($share['cnt'])) {
        $share = $db->prepare('INSERT INTO share SET posts_id=?, member_id=?'); 
        $share->execute(array(
            $_REQUEST['ret'],
            $_SESSION['id']
        ));

        $reposts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=?');
        $reposts->execute(array(
            $_REQUEST['ret'] ));

        $repost = $reposts->fetch();
        $repost_member = $repost['name'] .'さんがリツイート: ' . $repost['message'];
    
        $repost_message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, retweet_id=?, created=NOW()');
        $repost_message->execute(array(
            $_SESSION['id'],
            $repost_member,
            $_REQUEST['ret']
        ));
    
        
    } else {
        $share = $db->prepare('DELETE FROM share WHERE posts_id=? AND member_id=?');
        $share->execute(array(
            $_REQUEST['ret'],
            $_SESSION['id']
        ));

        $repost_message = $db->prepare('DELETE FROM posts WHERE member_id=? AND retweet_id=? AND message=?');
        $repost_message->execute(array(
            $_SESSION['id'],
            $_REQUEST['ret'],
            $repost_member
        ));
    }
}


header('Location: index.php'); exit();
?>