<?php 
/**
 * Example template for the login page.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage templates
 */
?>
<div class="login">
    <h1>Login</h1>
    <div class="login-form">
	<form action="<?=$this->link("")?>" method="post">
	    <label for="login">Login:</label>
	    <input id="login" type="text" tabindex="1" size="22" value="" name="login" /> 
	    <br />
	    <label for="password">Password:</label>
	    <input id="password" type="password" tabindex="1" size="22" value="" name="password" /> 
	    <br />
	    <input type="submit" name="submit" class="submit" value="Login" />
	</form>
	<?php if(isset($login_error)):?>
	<div class="login-error">
	Try again.
	</div>
	<?php endif; ?>
    </div>
</div>