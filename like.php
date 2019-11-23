<?php
session_start();
require('dbconnect.php');



if (isset($_SESSION['id']) && isset($_REQUEST['posts_id'])) {
   
    
    //いいねの重複を調べる
	$likes = $db->prepare('SELECT COUNT(*) AS cnt FROM likes WHERE posts_id=? AND member_id=?');
    $likes->execute(array(
        $_REQUEST['posts_id'],
        $_SESSION['id']
    ));
    $like = $likes->fetch();
   
    if ($like['cnt'] == 0) {
        $like = $db->prepare('INSERT INTO likes SET posts_id=?, member_id=?');
        $result = $like->execute(array(
            $_REQUEST['posts_id'],
            $_SESSION['id']
        ));
    } else {
        $like = $db->prepare('DELETE FROM likes WHERE posts_id=? AND member_id=?');
        $like->execute(array(
            $_REQUEST['posts_id'],
            $_SESSION['id']
        ));
    }
}

header('Location: index.php'); exit();
?>