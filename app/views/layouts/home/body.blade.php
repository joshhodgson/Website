@extends('layouts.base')

@section('body')
@yield('no-js-msg')
<div class="js-required">
@yield('side-banners')
<div class="container">
	<nav class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="<?=e($homeUri);?>"><img class="img-responsive" src="<?=asset("assets/img/logo.png");?>"/></a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					@yield('navbarList', '')
				</ul>
				<div class="navbar-right">
					<?php if($searchEnabled): ?>
					<button class="navbar-btn btn btn-link search-btn" type="button" data-search-query-uri="<?=e($searchQueryAjaxUri);?>"><span class="glyphicon glyphicon-search"></span></button>
					<?php endif; ?>
					<a class="btn <?=!$loggedIn ? "btn-primary" : "btn-default"?> navbar-btn" href="<?=e($loggedIn ? $accountUri : $loginUri);?>"><?=$loggedIn ? '<span class="glyphicon glyphicon-cog"></span>' : '<img width="14" height="14" src="'.asset("assets/img/fb-icon.png").'"/> Login'?></a>
				</div>
			</div>
		</div>
	</nav>
</div>
@yield('content')
<div class="page-footer-bg"></div>
<div id="footer" class="page-footer">
	<div class="container">
		<div class="footer-txt-container">
			<div><a href="https://github.com/LA1TV/Website" target="_blank">Click here to view the source code on GitHub.</a></div>
			<div class="admin-link"><a href="<?=e($adminUri);?>">Admin</a></div>
		</div>
	</div>
</div>
</div>
@stop
