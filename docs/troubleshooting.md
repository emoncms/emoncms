## Troubleshooting

#### If you get a 404 not found when you click on home or register

This is probably because you need to enable mod rewrite on your server. See step 4 of the Linux guide and step 2 of the windows guide.

#### If you get an mysql cannot write into database error

Check that the database settings in settings.php are correct and that the database user has full rights to the database.

#### If you try to upgrade emonCMS with an old database and you get this error: 

    Fatal error: Call to a member function fetch_array() on a non-object in ../Includes/db.php on line 50

It is because the database schema needs to be updated. Open emoncms in your browser and login with the admin user - this will be the first user you created. Then navigate to the Admin tab (top-right). Click 'Update & check' this will update your database. If the error prevents you from doing this, open index.php in a text editor and add the line: db_schema_setup(load_db_schema()); just above require("Modules/user/user_model.php"); If you go to emoncms again in your broser the error should now have disappeared. Remove the line once you have finished.


<div class='alert alert-info'>

<h3>Note: Browser Compatibility</h3>

<p><b>Chrome Ubuntu 23.0.1271.97</b> - developed with, works great.</p>

<p><b>Chrome Windows 25.0.1364.172</b> - quick check revealed no browser specific bugs.</p>

<p><b>Firefox Ubuntu 15.0.1</b> - no critical browser specific bugs, but movement in the dashboard editor is much less smooth than chrome.</p>

<p><b>Internet explorer 9</b> - works well with compatibility mode turned off. F12 Development tools -> browser mode: IE9. Some widgets such as the hot water cylinder do load later than the dial.</p>

<p><b>IE 8, 7</b> - not recommended, widgets and dashboard editor <b>do not work</b> due to no html5 canvas fix implemented but visualisations do work as these have a fix applied.</p>

</div>


