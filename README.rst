===============
DocTest for PHP
===============

.. caution:: This package is a work in progress, and does *not* guarantee any
   useful functionality yet. Your mileage may **seriously** vary.
   

Departures From Python's ``doctest`` Module
-------------------------------------------

* Test runners do not accept the ``$compileflags`` argument since PHP doesn't
  use special arguments for certain features that might be required by the
  test.
  
* Code level option flag constants are prefixed with ``DOCTEST_``. Specifying
  options from within doctests may (should) still use the non-prefixed names.
  
* Diff style output is not yet available, and the options associated with this
  feature will also differ from those found in Python.


Todo
----

* Finish Finder class (which will remove phpDoc formatting from tests).

* Add actual doctest options.

* Allow less strict formatting of exceptions.

* Fully support (and test) multibyte encodings.

* Review indentation handling since PHP shouldn't care.

* Get fancy with the ``xdiff`` extension.