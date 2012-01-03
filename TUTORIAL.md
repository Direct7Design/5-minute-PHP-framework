# Tutorial for 5-minute PHP framework

This very simple tutorial shows the basics of how to use this framework.

## The basics

### Configuration

Source files are divided into 2 main directories: `app` and `public_html` (there's also `docs` with documentation, but it's not important for
the framework itself). `app` directory contains the core files of the application, `public_html` is, on the other hand, the place where your server should 
point to.

All settings and configuration options are held in `public_html/index.php` file. The only thing that should be changed in this file is this array:

    $c->start(array(
        "absolute_url" => "http://localhost/example/", //with the slash
        "relative_url" => "/example/", //with the slash at the beggining and slash at the end
        "debug" => true,
        "crypt_std_key" => "wl2okswwxrqqw52k89kq3k1ou29xw66s",
    ));

To start with the framework you need to set at least the `absolute_url` and `relative_url` values. If you're placing the `public_html` directory in the root
of your server (for example, your `absolute_url` looks like this: http://www.example.com/), **you need to set the `relative_url` value to `"/"`**.

Also, if you're using a web-server other than Apache, you need to adjust the "rewrites" in your server's configuration to match the contents of `.htaccess`.
It is a very simple configuration: all requests that doesn't go to `www/` directory, should be directed to `index.php`.

### MVC architecture

After your configuration is set up, you can get into the `app` directory. As the framework is based on MVC concept there are 3 main directories here:
`controllers`, `models` and `templates`. 

#### Controllers

`controllers` directory holds every controller you may need for your application. There should **always** be a file named `main.php` with `appController` class 
 with `index()` method inside. Empty version of this file should look like this:

    class appController extends appCore {

        public function index() {

        }
    }

This is the only "special" controller in the application: `index()` method is called always for every empty request, i.e. every request than opens the "home page".
For example if your `absolute_url` is set to *http://www.example.com/*, every request coming to *http://www.example.com/* will be redirected to this `index()` 
method. 

All requests that do not come to the root directory (`absolute_url`) are treated with the standard MVC approach: **http://www.example.com/controller/action/param1/param2/.../**
If the `action` is not set (i.e. request comes to *http://www.example.com/controller/*), it will be redirected to the `index()` method in specified controller. 

See the example `welcomeController`: if the user accesses the *http://www.example.com/welcome/* page, his request will be passed to `welcomeController::index()` 
method. If the user opens the *http://www.example.com/welcome/someaction/* page his request will be passed to `welcomeController::someaction()`. 

**Note:** if the action called in url does not exist in the controller, the `index()` method will be called instead.



#### Models

`models` directory contains every model that may be needed in your application. Each model can be connected to a different database (right now: MongoDB or MySQL).
The type of the database that the model refers to is determined by the class that model extends.

`usersModel` that will be connecting to MongoDB database must extend the `databaseMongoDB` class:

    class usersModel extends databaseMongoDB {}

`usersModel` that will be connecting to MySQL database must extend the `databaseMysql` class:

    class usersModel extends databaseMysql {}

Each model contains two basic information that are required for it to work: `$_dbName` and `$_dbTable`.

`$_dbName` is the name of the database that the model refers to (each model can connect to different database),

`$_dbTable` is the name of the table (with MySQL) or collection (with MongoDB) that the model refers to.

So, if you want your model to connect to the database "test" and use the table (collection) "users", you should have the following code in your model:

    protected $_dbName = "test";
    protected $_dbTable = "users";




#### Templates

`templates` directory holds all HTML files that will be outputted to the end user. There should always be a file called `main.php`, which holds the "main"
HTML template of the whole page. Inside this file there should be a loop:

    <?php foreach($templates as $template){ include($template); }?>

Every other template file will be passed in the `$templates` array to this file. You can, of course, adjust this file accordingly to your needs.

There are also special templates starting with the `error_` prefix in the file name. Each file like this will be used to show an error message to the user.
For example, file `error_404.php` will be shown in case of the 404 error. Note that error templates must contain *whole* HTML page, as they will NOT be included
inside the `main.php`.
Refer to the existing template files for the examples.


## Using templates with controllers

Usage of the templates inside of the controllers is very simple. Every action on the templates should be done on the `$this->view()` object.
For example to show a template called `welcome.php`:

    $this->view()->addTemplate("welcome");

The name passed to the `addTemplate` method does not have to end with ".php" (it will be added automatically if it's not there).

If you want to pass a variable to the template (for example: show a user's name):

    $this->view()->addTemplateVal("username", "Paul");

Now, you can use the `$username` variable in the `welcome.php` template:

    <div>
        <h1>Welcome <?=$username?>!</h1>
    </div>

will result in the output HTML:

    <div>
        <h1>Welcome Paul!</h1>
    </div>


If you want to show more than one template (for example, the menu and the welcome page), you can add as many templates as needed:

    $this->view()->addTemplate("menu");
    $this->view()->addTemplate("welcome");

Note that the order *does* matter. In the example above HTML from the `menu` file will be included above the HTML from `welcome` page.
On the other hand, order does *not* matter for the `addTemplateVal` - you can add as many values as you need:

    $this->view()->addTemplateVal("username", "Paul");
    $this->view()->addTemplateVal("city", "New York");
    $this->view()->addTemplateVal("date", date("Y-m-d"));

And all of those variables (`$username`, `$city` and `$date`) will be available in ALL templates added by `addTemplate()`.

If you need to add additional javascript files to the output page, `main.php` template contains a special loop for this:

    <?php if(isset($extraJs) && !empty($extraJs)): 
        (...)
    endif;?>

To use it, all you need is:

    $this->view()->addJs("my_additional_script.js");


### Responding to AJAX

In the framework there's no difference between the "normal" request and AJAX request. So any AJAX call will be responded with the full HTML output.
If you do not need that behavior (for example, you only want to the a partial HTML to be inserted to the page) you can change it:

    $this->view()->setAjax(true);
    $this->view()->addAjaxTemplate("ajax");

This way, only the contents of the `ajax.php` template file will be send back. You can, of course, add as many ajax templates as you need.

In addition to that, the framework makes it very easy to make a JSON responses. If you need to respond with json, all you need is:

    $this->view()->sendAjax($somecontenthere);


## Using models with controllers

Usage of the models in the controllers is very simple. To use the example `usersModel`:

    $usersModel = $this->db()->getModel("users");

The variable name (`$usersModel`) of course doesn't matter. 

Now you can use all methods defined in this model:
    
    $result = $usersModel->getUser($login, $password);

The `$result` variable should now hold the appropriate `databaseResult` object (`MongoDBResult` for MongoDB models, `MySqlResult` for MySQL models).

The simplest usage of the databaseResult objects:

    $user_login = $result->get("login");

Where the `"login"` is a name of the field in the result row you want to get (this example is, of course, dummy, as we already know the login,
 but that's not the point).

You can also retrieve more than one row (MySQL) or document (MongoDB):

    $users = $usersModel->getAll();

The result object in `$users` variable will be the same (appropriate `databaseResult`), but can be iterated like this:

    $users_names = array();
    foreach($users as $user){
        $users_names[] = $user->get("login");
    }

Please refer to the appropriate `databaseResult` object documentation for information what more can be done with this objects.


## Using helpers

The `app` directory contains also the `helpers` folder. Helpers are simple classed that can be used anywhere. Please refer to the `menuHelper.php` file
for an example how to create the helper. All the features that can be done in controllers, can be also used in helpers (that means models, templates, etc.).

The use the example `menuHelper` is simple:

    $menuHelper = new menuHelper();
    $menuHelper->getMenu();


## More information

For any additional information about the framework, please refer to the documentation. It can be found in the `docs/` directory or at the
[GitHub pages for this repository](http://pbudzon.github.com/5-minute-PHP-framework/) (*Documentation* section).