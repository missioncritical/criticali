Critical I
==========

What Is It?
-----------

What is Critical I?  Well, the I is for infrastructure (as in a framework),
but rather than prattle on with a lot of grandiose ideas about what it's
meant to be and the philosophy behind it, it's probably a lot more
effective to give an example of what you can actually do with it.  As an
example, we'll create a trivial web application for maintaining a list of
notes.

An Example
----------

### Install It

The best way to get Critical I is to use the installer. Download it from
[http://criticali.missioncriticallabs.com/get-criticali](http://criticali.missioncriticallabs.com/get-criticali).
Save the file as get-criticali.php and then run it with the command:

    $ php get-criticali.php

### Get The Packages You'll Need

Once you install Critical I, you'll have an empty repository. The
repository is for storing packages you want to add to projects. For this
exercise, we'll need to download a few packages for a simple project. We
can grab everything we need with the command:

    $ criticali add --yes mvc

To get more information about any of the available criticali commands,
type "criticali help *command*" at the command line or "criticali help
commands" to see a list of commands.

### Create A Project

The next step is to create a new, empty project (web application).  Again,
from my home directory:

    $ criticali project-init notes

Then, inside the project, add the MVC framework:

    $ cd notes
    $ criticali project-add mvc
    
This command "freezes" the library components we're using (in this case,
the MVC components) inside our project directory so that deployment is
simply a matter of copying the directory up to the server.  Critical I
provides commands for managing the library components that are part of a
project.

### Setup The Database

I'll use a migration to create the database table for my application.
Once again using my favorite text editor, I'll create the file
private/migrations/001_CreateNotesTableMigration.php with the content:

    <?php
    
    class CreateNotesTableMigration extends Migration_Base {
      public function up () {
        $this->exec("
          CREATE TABLE notes (
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            title varchar(255) NOT NULL,
            content text NOT NULL
          )
        ");
      }
      
      public function down() {
        $this->exec("DROP TABLE notes");
      }
    }
    
    ?>

And then I'll run it:

    $ criticali project-migrate

### Create The Model

I need a model for my notes table.  It's declared in a new file in
private/models/Note.php.  The code is:

    <?php
    
    class Note extends ActiveRecord_Base {
    }
    
    ?>

### Create The Controller

Now I'll create my controller for basic notes operations.  The controller
goes in private/controllers/NotesController.php and looks like:

    <?php
    
    class NotesController extends Controller_Base {
      public function index() {
        $note = new Note();
        $this->notes = $note->find_all();
      }
      
      public function add() {
        $this->note = new Note();
      }
      
      public function create() {
        $this->note = new Note($_POST['note']);
        if ($this->note->save()) {
          $this->set_flash('notice', 'Note created.');
          $this->redirect_to('/notes');
        } else {
          $this->set_flash('notice', 'Could not create note.');
          $this->render_action(array('action'=>'add'));
        }
      }
      
      public function edit() {
        $this->note = new Note();
        $this->note = $this->note->find($_REQUEST['id']);
      }
      
      public function update() {
        $this->note = new Note();
        $this->note = $this->note->find($_REQUEST['id']);
        if ($this->note->update_attributes($_POST['note'])) {
          $this->set_flash('notice', 'Note updated.');
          $this->redirect_to('/notes');
        } else {
          $this->set_flash('notice', 'Could not update note.');
          $this->render_action(array('action'=>'edit'));
        }
      }
      
      public function delete() {
        $this->note = new Note();
        $this->note = $this->note->find($_REQUEST['id']);
        $this->note->destroy();
        $this->set_flash('notice', 'Note deleted.');
        $this->redirect_to('/notes');
      }
    }
    
    ?>

### Create The Views

Lastly, we'll need several view files.	To keep this example easy to
understand, I'll stick to simple and straightforward HTML.  Prettying it up
a bit and following better development conventions (for example, posting
delete requests) can be left as an exercise for the reader.

By default, the Critical I MVC framework uses Smarty templates.  The view
for each action in a controller is a separate file in a folder named for
the controller all inside of private/views.  So, getting right to the
point, I'll create private/views/notes/index.tpl:

    <h1>List of Notes</h1>
    
    <table>
      <thead>
        <tr>
          <th>Action</th>
          <th>Title</th>
        </tr>
      </thead>
      <tbody>
      {foreach from=$notes item=note}
        <tr>
          <td><a href="/notes/edit/{$note->id|escape}">edit</a>
              <a href="/notes/delete/{$note->id|escape}">delete</a></td>
          <td>{$note->title|escape}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
    
    <p><a href="/notes/add/">Add New</a></p>

Then on to private/views/notes/add.tpl:

    <h1>New Note</h1>
    
    {errors for=$note}
    
    <form action="/notes/create" method="post">
    {include file="notes/_form_fields.tpl"}
    </form>

And the referenced private/views/notes/\_form\_fields.tpl:

    <table>
    <tr>
      <td><label for="note_title">Title</label></td>
      <td>{text_field var="note" attr="title"}</td>
    </tr>
    <tr>
      <td><label for="note_content">Content</label></td>
      <td>{text_area var="note" attr="content"}</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="save" value="Save" /></td>
    </table>

Next private/views/notes/edit.tpl:

    <h1>Edit Note</h1>
    
    {errors for=$note}
    
    <form action="/notes/update/{$note->id}" method="post">
    {include file="notes/_form_fields.tpl"}
    </form>

Those are the views I need, but I also want to wrap them with some common
HTML, so I'll set up a default layout by creating the file
private/views/layouts/application.tpl:

    <!DOCTYPE html>
    <html>
      <head>
        <title>Example Notes Application</title>
      </head>
      <body>
        {message_area name="notice"}
        
        {include file=$content}
      </body>
    </html>

### Viewing The App

That's everything I need for basic operations in my notes application.	To
run the app, I'll set up a new virtual host in my favorite web server
running some flavor of PHP 5.  I'll configure the document root to be the
notes directory that was created when I initialized the project.  I'm
running Apache, so I just drop this in with my other virtual host
declarations:

    <VirtualHost *:80>
      ServerName notes.local
      DocumentRoot "/Users/jhunter/notes"
      <Directory /Users/jhunter/notes>
        Allow from all
        Order allow,deny
        AllowOverride All
      </Directory>
    </VirtualHost>

And add an entry for notes.local in /etc/hosts:

    127.0.0.1    notes.local

I'll make sure that everything in private/var is writable by the user the
web server runs and, and then I can just gracefully restart Apache and
point my browser at http://notes.local/notes/.

I should see the index page from my new controller:

![index page screen shot](http://criticali.missioncriticallabs.com/images/notes/notes_screenshot001.png "Empty index page of Notes controller")

I can also add a new note:

![add page screen shot](http://criticali.missioncriticallabs.com/images/notes/notes_screenshot002.png "Add page of Notes controller with information entered")

and see that show up as well:

![index page screen shot](http://criticali.missioncriticallabs.com/images/notes/notes_screenshot003.png "Index page of Notes controller showing newly added note")

### Validations

There are quite a few next steps that could be taken to improve our notes
application, not the least of which might be adding a little CSS to
beautify things a bit, but in terms of functionality built into the
library, adding a little validation code to our model would make a good
final example for this intro.  Let's say that for our app we want to to
make sure every note has a title and content.  It's easy to update our
model class to include that:

    <?php
    
    class Note extends ActiveRecord_Base {
      protected function init_class() {
        $this->validates_presence_of(array('title', 'content'));
      }
    }
    
    ?>

Now if I try to create or save a note without these, I'll get an error
message:

![add page screen shot](http://criticali.missioncriticallabs.com/images/notes/notes_screenshot008.png "Add page of Notes controller with error message after trying to save a blank note")

More Information
----------------

Ready to dig deeper into Critical I and find out more about it?  The best
place to start is the [API documentation](http://criticali.missioncriticallabs.com/docs/).
