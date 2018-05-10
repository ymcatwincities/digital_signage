Digital Signage
--------------------------------------------------------------------------------
The Digital Signage module provides tools for creating and managing content that
could be used on Digital Signs.

Requirements
--------------------------------------------------------------------------------
Digital Signage Drupal 8 requires the following:

* Drupal
  https://www.drupal.org/project/drupal
  The Drupal version  8.2 and above.
* Open Y
  https://www.drupal.org/project/openy
  The Open Y platform is a content management system that uses Drupal 8
  functionality and useful modules from YMCAs and digital partners.
* Panels
  https://www.drupal.org/project/panels
  The Panels module allows a site administrator to create customized layouts
  for multiple uses.
* Panelizer
  https://www.drupal.org/project/panelizer
  The Panelizer module allows you to attach Panels to any node in the system.
* CKEditor Font Size and Family
  https://www.drupal.org/project/ckeditor_font
  This module enables the Font Size and Family plugin from CKEditor.com in your
  WYSIWYG.
* Moment.js library
  https://github.com/moment/moment/releases
  The library should be added to the /libraries/moment folder. Supported version
  is 2.18.0 and above.
* Moment.js Timezone library
  https://github.com/moment/moment-timezone/releases
  The library should be added to the /libraries/moment-timezone folder.Supported
  version is 0.5.14 and above.

Submodules
--------------------------------------------------------------------------------
This module provides a set of submodules:
* ds_datetime_range - helper module needed for creating date widget and
formatter.

* time_range - helper module needed for creating time range widget.

* openy_digital_signage_classes_schedule - this module provides an entity that is
used for creating classes sessions and displaying them on screens. All
integrations like GroupEx Pro, Personify should use the entity provided by this
module.

** openy_digital_signage_groupex_schedule - this is a MVP version of
integration with GroupEx Pro. It has some unmet dependencies and could not be
used at this moment.

** openy_digital_signage_personify_schedule - this is a MVP version of
integration with Personify. It has some unmet dependencies and could not be
used at this moment.

** openy_digital_signage_room - this module is used to create rooms/studios
and link them with classes sessions to display different classes on different
screens.

* openy_digital_signage_schedule - this module provides the possibility create
as many as you want different schedules for screens.

* openy_digital_signage_screen - this module provides a custom entity that
represents a real screen, provides a URL that should be used on a screen, and
has a reference to screen schedule.

* openy_digital_signage_screen_content - the main module that provides content
type, listing pages, and interaction with panelizer and panels IPE.

** openy_digital_signage_blocks - this is a submodule, that provides a set of
blocks that allow creating content via panel IPE.

The UI at this point provides a front-end interface for creating and managing
content that could be displayed in real Digital Signs.

Features
--------------------------------------------------------------------------------
* The possibility create screens
* The possibility create screen schedules
* The possibility create schedule items
* The possibility create screen contents
* The possibility create class sessions manually
* Integration with GroupEx Pro(require module which currently is not part of this project)
* Integration with Personify(require module which currently is not part of this project)
* Different settings.

Standard usage scenario
--------------------------------------------------------------------------------
1. Install the main module Digital Signage.
2. Open /admin/digital-signage.
3. Go to Screens
4. Add a new screen
   4.1 Enter screen name in the Title field
   4.2 Enter machine name
   4.3 Select orientation
   4.4 Choose location
   4.5 Fell free to enter something into option fields
   4.6 Select type of the screen
   4.7 On the second step
       4.7.1 Create new schedule
       4.7.2 Enter title and description
       4.7.3 Choose default fallback screen(you can edit this later).
5. On the manage schedule screen:
   5.1 Click on the + icon and add a new item to the schedule
   5.2 Fill all required fields
   5.3 Create new screen content or choose already existing
   5.4 Save
6. On the right side find Panels IPE toolbar to manage the content

Known issues
--------------------------------------------------------------------------------
* We have not tested this module with Drupal 8.5.* and above.

* The module does not work with Drupal 8.3 and below.

* The module does not work with Open Y 8.1.* and below.


Credits / contact
--------------------------------------------------------------------------------
Currently maintained by Dmitry Drozdik [2] and Andrey Maximov [1].

Originally developed for YMCA of Greater TwinCities.
Ongoing support & development is sponsored by Five Jars.

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/openy_digital_signage

References
--------------------------------------------------------------------------------
1: https://www.drupal.org/u/andreymaximov
2: https://www.drupal.org/u/ddrozdik
3: https://www.drupal.org/u/podarok
