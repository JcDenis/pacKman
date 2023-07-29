2023.07.29
- require Dotclear 2.27
- require php 8.1+
- update to Dotclear 2.27-dev

2023.05.29
- require dotclear 2.26
- require php 8.1+
- fix save settings
- add option to separate themes and plugins repositories

2023.05.24
- require dotclear 2.26
- require php 8.1+
- add option to select non latest versions

2023.05.13
- require dotclear 2.26
- require php 8.1+
- use define php_min
- use html helper everywhere

2023.04.27
- require dotclear 2.26
- require php 8.1+
- rollback Zip Unzip dotclear class
- fix settings read/write

2023.04.22
- require dotclear 2.26
- require php 8.1+
- remove custom Exception handler
- add plugin Uninstaller features

2023.04.06
- update to latest dotclear 2.26-dev changes
- use Helper Form
- cleanup main class

2023.03.19
- fix init check
- fix some phpstan warnings

2023.03.18
- require PHP 8.1
- use new (un)zip helpers
- use class for settings
- add setting to hide distributed modules

2023.03.14
- fix preg match on excluded files

2023.03.11
- require Dotclear 2.26
- use PHP namespace
- use dcPage open/close module
- use Form helpers
- manage modules structure > dc 2.24

2023.01.07
- fix previously introduced unix bug

2023.01.05
- harmonise structure (with my plugins)

2022.12.20
- use shorter settings names
- use dotclear VAR as default repository
- use notice for errors
- declare strict_type

2022.12.19.1
- playing with namespace

2022.12.03
- cope with disabled modules

2022.11.20
- fix compatibility with Dotclear 2.24 (required)

2022.02.01
- fix module info (id/root) before zip
- use dc2.21 new svg icon by @franck-paul

2021.11.06
- update translation (thanks @Philippe-dev)
- update to PSR12

2021.10.28
- add option to convert newline from files content on the fly
- clean up again...
- fix wrong version on repository
- fix modules sort
- hide empty tabs

2021.08.22
- fix PSR2 coding style
- update license
- fix help

2021.08.17
- move to Franck style

2013.11.15
- Fix all forms: Use modules root intead of Id

2013.10.28
- Change behaviors arguments
- Typo and minor fixes

2013.10.26
- Switch to DC 2.6
- Fix use of dcThemes, thx franckpaul
- New icon, thx kozlika
- Add dashboard icon
- Clean up code and (again) lighter admin interface

2013.05.11
- Added option to remove comments from files
- Fixed page title and messages and contents

0.5.1 - 2010-10-12
- Fixed install on nightly build
- Fixed missing namespace on admin

0.5 - 2010-06-05
- Switched to DC 2.2
- Changed admin interface (easy, light, fast)
- Added direct download button on repository (closes #449)

0.4 - 2009-10-10
- Fixed second package management
- Fixed subfolder in filename
- Added install and uninstall features
- Added help
- Added LICENSE
- Cleaned up