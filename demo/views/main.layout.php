<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>koala demo</title>
<link href="/static/css/main.css" rel="stylesheet" type="text/css" />
</head>
<body>
<!-- title bar -->
<div id="header">
    <h1>管理后台</h1>
    <sup>v1.1.1</sup>
    <span>管理员:<?=$admin?></span>
</div>
<div id="nav">
        <a href="/admin/member" class="active">用户管理</a><a href="/admin/member/credit">积分管理</a><a href="/admin/comment">评论管理</a>
</div>
<?=$layout_contents?>
<div id="footer">
        (C)opyright 2015, Powered by <a href="https://github.com/nixuehan/koala">koala micro web-framework</a>
    </div>
</div>

</body>
</html>
