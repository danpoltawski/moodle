CONTRIBUTING TO MOODLE
======================

Moodle is made by people like you. We are members of a big worldwide community
of developers, designers, teachers, testers, translators and other users. We
work at universities, schools, companies, various organisations and other
places. Please feel welcome and encouraged to join us and contribute to the
project.

You don't need to be a developer to contribute. There are many opportunities
and ways you can help. See <https://docs.moodle.org/dev/Contributing_to_Moodle>
for some suggestions.

Moodle is known for being open to community contributions - yet having strong
quality assurance mechanisms in place (peer-review, automated behaviour
testing, continuous integration, human post-integration checks and others).

Pull requests
-------------

PLEASE DO NOT FILE PULL REQUESTS via Github. The repository there is just a
mirror of the official repository at <https://git.moodle.org/>. Issues are
reported and patches provided via <https://tracker.moodle.org>. See details
below.

Moodle core bugs and features
-----------------------------

During the years of intensive development, a mature process of including
submitted patches has evolved.  It is fully described in details at
<https://docs.moodle.org/dev/Process>. Shortly:

* For every bug fix or a feature, an issue in the tracker must exist.
* You publish the branch implementing the fix or the feature in your public
  clone of the moodle.git repository (typically on Github).
* Your patch is peer-reviewed, discussed, integrated, tested and then released
  as a part of moodle.git.
* New features are developed on the master branch. Bug fixes are also
  backported to the currently supported maintenance (stable) branches.

Moodle plugins
--------------

Moodle is also a framework for developing additional plugins that extend
standard features. We have a Moodle Plugins directory available at
<https://moodle.org/plugins/> where you can register and maintain your plugin.
Plugins hosted in the plugins directory can be easily installed and updated via
the Moodle administration interface.

See <https://docs.moodle.org/dev/Plugin_contribution> guidelines for more
details. Shortly:

* You are expected to have a public source code repository with your plugin
  code.
* You can register your plugin with the Plugins directory. The plugin must be
  approved to be published.
* You are expected to continuously release updated versions of the plugin via
  the plugin directory. We do not pull from your code repository, you must do
  it explicitly.

Translations
------------

You can easily provide translations of the Moodle user interface texts into
your language. Both Moodle core and plugins submitted to the Plugins directory
can be translated via an online interface at <https://lang.moodle.org>.

* Create an account and log in at lang.moodle.org.
* Locate the strings you want to translate (e.g. missing strings for particular
  plugin).
* Provide the translation and submit it for the language pack maintainer for
  review. Once your contribution is accepted, it will become part of the
  automatically generated language pack files.

Wiki documentation
------------------

Moodle uses MediaWiki (the same system that Wikipedia runs on) for the user and
developer documentation available at <https://docs.moodle.org>. You can
significantly contribute to Moodle by improving and updating the wiki
documentation there.

Quality assurance testing
-------------------------

You will be praised for performing valuable tests of integrated code changes.
See more details at <https://docs.moodle.org/dev/Tracker_introduction> and at
<https://docs.moodle.org/dev/QA_testing>.

Get in touch
------------

Join the community discussions at <https://moodle.org/community> where you can
find forums in many languages focused on particular areas of interest, such as
development, themes design, security and others.
