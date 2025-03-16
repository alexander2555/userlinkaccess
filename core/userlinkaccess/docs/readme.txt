--------------
UserLinkAccess
--------------

Author: Alexander Mamontov <alexander.m.190180@hotmail.com>

UserLinkAccess is a component for MODX 3.x that allows you to grant temporary link access to a resource page.

Installation

Install by package management.
Configure the component parameters from 'userlinkaccess' namespace if necessary.
Bootstrap is necessary for frontend correct output (https://getbootstrap.com/docs/5.3/getting-started/download/)
It is recommended to install the CSRFHelper package (https://docs.modmore.com/en/Open_Source/CSRFHelper/index.html)

Usage

To directly generate an access link you can use snippet:

[[UserLinkAccess? &resourceId=`10` &lifetime=`3600`]]

Link generating form:

[[$UserLinkAccessFormTpl]]

Also, when creating and deleting a link, the corresponding hooks are executed (by default, these are UserLinkAccessCreateHook and UserLinkAccessDeleteHook snippets).
They can be overridden in the corresponding settings (userlinkaccess.userlinkaccess_create_link_hook, userlinkaccess.userlinkaccess_delete_link_hook).