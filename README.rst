===============
DocTest for PHP
===============

--------------------------------
The missing link in PHP testing.
--------------------------------

:Author: Greg Thornton
:Contact: xdissent@gmail.com

.. caution:: This package is a work in progress, and does *not* guarantee any
   useful functionality yet. Your mileage may **seriously** vary.

.. contents::


Project Status
--------------

Currently, parsing, running and reporting of tests is fully functional, but
(perhaps ironically) tests contained within documentation comments are not
fully supported yet. Text files with tests are parsed correctly and
can be used at this very moment, but a little more work is required before
comments in your code will be correctly parsed for tests. Preliminary support
is finished (see the DocTest::testObj() static method) but it is not recursive
and cannot search whole files at all yet.

   
Surely DocTest for PHP Already Exists, Right?
---------------------------------------------

That's what I thought too! And yes, in a way, it *does* exist. There's already 
a `PHP port`_ of ``doctest`` called ``doctest-php``. Unfortunately, it's 
only documented in Korean (very sparsely) and doesn't work for me out of the
box. Additionally, I believe it was developed at a time when PHP's interactive
command line mode wasn't available, since the tests are expected to be 
written in a slightly different format, with output being printed for *any*
return value (like Python) regardless of whether output was requested (via 
``echo``, etc). Lack of documentation aside, the differences between test 
syntax and real-world command line sessions is a huge deal-breaker for any
reasonable test-nazi like myself.

.. _PHP port: http://code.google.com/p/doctest-php

It should also be noted that PEAR `contains a package`_ called 
``Testing_DocTest`` which seems to be completely unaware of the existence
of the new interactive PHP command line mode, and requires a foreign syntax
that looks absolutely nothing like Python's doctest. Not even close!

.. _contains a package: http://pear.php.net/package/Testing_DocTest


Departures From Python's ``doctest`` Module
-------------------------------------------

* Test runners do not accept the ``$compileflags`` argument since PHP doesn't
  use special arguments for certain features that might be required by the
  test.
  
* Code level option flag constants are prefixed with ``DOCTEST_``. Specifying
  options from within doctests may (should) still use the non-prefixed names.
  
* Diff style output is not yet available, and the options associated with this
  feature will also differ from those found in Python.
  
* Although it's more accurately a difference between Python's interpreter and
  PHP's, it's important to note that simply returning a value in PHP's 
  interactive mode will *not* result in any "expected output." This means tests
  in PHP have to be slightly more verbose (using ``var_dump()`` etc). The only
  thing you have to remember really, is that if it's not output when you're
  in a *real* PHP interactive mode session, it shouldn't be expected as output
  when writing tests.


Todo
----

* Move all constants to class constants of ``DocTest`` if possible (ditching prefix).

* Finish Finder class (which will remove phpDoc formatting from tests).

* Add the familiar ``testMod()`` static method (requires completed Finder class).

* Allow less strict formatting of exceptions.

* Check exception type and message independently, since Python handles both as one
  "exception message".

* Change ``IGNORE_EXCEPTION_DETAIL`` flag to ``IGNORE_EXCEPTION_MESSAGE`` and/or 
  ``IGNORE_EXCEPTION_TYPE``.

* Fully support (and test) multibyte encodings.

* Review indentation handling since PHP shouldn't care.

* Get fancy with the ``xdiff`` extension.

  * Update option flags to handle different diff types provided by ``xdiff``.
  
  * Override diff options if ``xdiff`` extension is not available.