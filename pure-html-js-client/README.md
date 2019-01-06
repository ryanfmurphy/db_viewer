DB Viewer - Pure JS Client
==========================

The `/php` directory contains a traditional PHP web app with views and API/controller functionality.

This directory is intended to provide an alternative but similar Client that still uses the same PHP API
but is pure HTML and Javascript - no PHP required for the frontend code.

## Principles and Dev Plan

* Trying to KISS - currently eschewing frameworks and React. Perhaps I'm too smug.
* Sticking with traditional JS - whatever's in the Browser. Not using npm/node/babel. Again, probably just smug and silly.
* Starting by moving HTML views over from PHP to straight HTML files (losing all dynamicness)
* Any frontend "component" that will dynamically change as part of the UI needs to become its own Component object

## Component Objects

* Naively mimicking the "important" parts of how a React app is organized, while not actually using React
* A Component object is a JS Object that contains:
  * A render(props) function returning HTML, optionally taking an object of props
  * A constructor function that creates a new Object of this type, and is used in the Render HTML

