
<?php
session_start();
require('dbconnect.php');
if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();
	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();
} else {
	// ログインしていない
    header('Location: login.php');
    exit();
}


//!!! リツイート機能のため追加箇所①　start　!!!//
//投稿を記録する
if(!empty($_POST)) {
    if ($_POST['message'] != '') {
        $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, retweet_id=?, created=NOW()'); //リツイートする投稿のidを記録する
        $message->execute(array(
            $member['id'],
            $_POST['message'],
            $_POST['reply_post_id'],
            $_POST['retweet_id']//リツイートする投稿のidを記録する
        ));

        header('Location: index.php');
        exit();
    }
}
//!!! リツイート機能のため追加箇所①　finish　!!!//

//返信の場合
if (isset($_REQUEST['res'])) {
    $response = $db->prepare('SELECT m.name,m.picture,p.* 
    FROM members m, posts p 
    WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
    $response->execute(array($_REQUEST['res']));

    $table = $response->fetch();
    $message ='@' . $table['name'] . ' ' . $table['message'];
}

//投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
    $page = 1;
}
$page = max($page,1);

//最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page -1) * 5;


//!!! いいね機能のため追加箇所①　start　!!!//
//投稿を取得　 投稿IDといいねされた投稿IDをリレーション 
$posts = $db->prepare('SELECT m.name, m.picture, p.*, COUNT(l.posts_id) AS like_cnt
FROM members m, posts p LEFT JOIN likes l ON p.id=l.posts_id 
WHERE m.id=p.member_id GROUP BY p.id ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();
//!!! いいね機能のため追加箇所①　finish　!!!//


//!!! リツイート機能のため追加箇所②　start　!!!//
//リツイートする投稿を取り出す
if (isset($_REQUEST['ret'])) {
    $retweet = $db->prepare('SELECT m.name, m.picture, p.* 
    FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
    $retweet->execute(array($_REQUEST['ret']));

    $retweets = $retweet->fetch();
    $share =$retweets['name'] . 'さんの投稿をリツイート' . ': ' . $retweets['message'];
}
//!!! リツイート機能のため追加箇所②　finish　!!!//


// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES);
}

//本文内のURLにリンクを設定
function makeLink($value) {
    return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>' , $value);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="/icon/iconstyle.css" />
</head>

<body>
<div id="wrap">
    <div id="head">
        <h1>ひとこと掲示板</h1>
    </div>
    <div id="content">
        <div style="text-align: right"><a href="logout.php">ログアウト</a></div>
        <form action="" method="post">
            <dl>
                <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
                <dd>
                <!--//!!! リツイート機能のため追加箇所③　start　!!!//-->
                <textarea name="message" cols="50" rows="5"><?php echo h($message); ?><?php echo h($share); ?></textarea><!--リツイート用に取り出したメッセージをtextarea初期値に設定-->
                <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
                <input type="hidden" name="retweet_id" value="<?php echo h($_REQUEST['ret']); ?>" /> <!--リツイートする投稿のidを記録する-->
                <!--//!!! リツイート機能のため追加箇所③　finish　!!!//-->
                </dd>
            </dl>
            <div>
                <input type="submit" value="投稿する" />
            </div>
        </form>

    <?php foreach ($posts as $post): ?>
        <div class="msg">
        <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48"
        alt="<?php echo h($post['name']); ?>" />
        <p><?php echo makeLink(h($post['message'])); ?><span class="name"> (<?php echo h($post['name']); ?>)</span>
        [<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]</p>
        <p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
        <?php
        if ($post['reply_post_id'] > 0):
        ?>
            <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
        <?php endif; ?>
        <?php
        if ($_SESSION['id'] == $post['member_id']):
        ?>
            [<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#F33;">削除</a>]
        <?php endif; ?>
        
        <!-- いいね画面表示部分　--> 
        <?php
        $likes = $db->prepare('SELECT * FROM likes WHERE posts_id=? AND member_id=?');
        $likes->execute(array(
        $post['id'],
        $_SESSION['id']
        ));
        $like = $likes->fetch(); ?>

        <?php if (!empty($like)): ?>
        <a href="like.php?posts_id=<?php echo h($post['id']); ?>">&#128147;</a>
        <?php else : ?>
        <a href="like.php?posts_id=<?php echo h($post['id']); ?>">&#9825;</a>
        <?php endif; ?>
        <!-- いいね数表示部分 -->
        [<span><?php echo h($post['like_cnt']); ?></span>]


        <!-- リツイート画面表示部分 -->
        [<a href="index.php?ret=<?php echo h($post['id']); ?>">リツイート</a>]
        <!-- リツイート数表示部分 -->
        <?php
        $reposts = $db->prepare('SELECT COUNT(*) AS share_cnt FROM posts WHERE retweet_id=?');
        
        $reposts->execute(array($post['id']));
        $repost = $reposts->fetch();
        ?>
        [<span><?php echo h($repost['share_cnt']); ?></span>]
        </p>
        </div>
    
    <?php endforeach; ?>

    <ul class="paging">
    <?php
    if ($page > 1) {
    ?>
    <li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
    <?php
    } else {
    ?>
    <li>前のページへ</li>
    <?php
    }   
    ?>
    <?php
    if ($page < $maxPage) {
    ?>
    <li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
    <?php
    } else {
    ?>
    <li>次のページへ</li>
    <?php
    }
    ?>
    </ul>


</div>
</body>
</html>