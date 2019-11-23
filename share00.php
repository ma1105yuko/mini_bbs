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
    //過去にリツイートしたことがなかった場合
    if (empty($share['cnt'])) {
        $reposts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=?');
        $reposts->execute(array(
            $_REQUEST['ret'] ));

        $repost = $reposts->fetch();
        $repost_member = $repost['name'] .'さんがリツイート:' . $repost['message'];
    
        $repost_message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, created=NOW()');
        $repost_message->execute(array(
            $_SESSION['id'],
            $repost_member  
        ));
        $auto_id = $db->prepare('SELECT last_insert_id() AS new_id FROM posts');
        $auto_id->execute(); 

        $get_auto_id = $auto_id->fetch();


        $share = $db->prepare('INSERT INTO share SET posts_id=?, member_id=? share_posts_id=?'); 
        $share->execute(array(
            $_REQUEST['ret'],
            $_SESSION['id'],
            $get_auto_id['new_id']
        ));

    //過去にリツイートしたことがある場合（shareテーブルとpostsテーブルを削除）
    } else {
        $share = $db->prepare('DELETE FROM share WHERE posts_id=? AND member_id=?');
        $share->execute(array(
            $_REQUEST['ret'],
            $_SESSION['id']

        ));

        $repost_message = $db->prepare('DELETE p FROM posts p LEFT JOIN share s ON p.id = s.share_posts_id WHERE p.id=?');
        $repost_message->execute(array(
    
            $get_auto_id['new_id']
        ));
    }
}


//header('Location: index.php'); exit();
?>