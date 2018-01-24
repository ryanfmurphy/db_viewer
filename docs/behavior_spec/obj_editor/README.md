Object Editor
=============

The Object Editor is a view that allows
Inserting and Updating rows ("objects") within the Database.

For more information, see the [Object Editor README file](/obj_editor/README.md).

Component-wise, it consists of:

* a Top Area containing statuses and options
* a Form containing the Fields and allowing you to Create or Edit the Object
    * including the Submit Buttons at the bottom of the Form


Top Area
--------

The Top Area contains:

* the "Choose Table" Link allowing you to switch to a different Table
* the Current Table Description, which can be clicked on to edit it,
    * when clicked, it turns it into a Select Table Input

### "Choose Table" link

The "Choose Table" Link takes you back to a blank Object Editor screen
where the Table has not yet been specified,
allowing you to choose a new Table.

You can also choose the Table by clicking the Current Table Description,
causing it to turn into a Select Table Input.

### Current Table description

This tells you what Table of the Database
you are currently Inserting / Editing a Row in.

When you click it, it turns into a Select Table input,
allowing you to change which Table
you are currently Inserting / Editing a Row in.

#### Select Table input

This allows you to Edit / Specify which Table
you are currently Inserting / Editing a Row in.

Type the Table Name into the box and press Enter.


Form
----

The Form contains:

* The Form Fields - each has Name Label and a Form Field Input
* The Submit Buttons

### Form Fields

The Form Fields contain:

* a Name Label showing the Name of the Form Field
* a Form Input allowing the User to View, Edit and/or Specify
  the Value of the Form Field

#### Name Label

The Name Label of a Form Field shows the Name of the Field.

Clicking on the Name Label causes the Form Field
to be Deleted from the Form.  Note that this does not
actually delete the Field from the DB - it merely
removes it from the Form Submission so that that Field
will not be written/updated into the Row when you hit
a Submit button.

#### Form Input

The Form Input allows the User to View, Edit and/or Specify
the Value of the Form Field.

It can either be an `<input>`, `<textarea>` or `<select>` type.

##### `<input>` type Form Inputs

This allows a user to View, Edit and/or Specify
a short / 1-line Value in a Form Field.

##### `<textarea>` type Form Inputs

This allows a user to View, Edit and/or Specify
a long / multi-line Value in a Form Field.

##### `<select>` type Form Inputs

This allows a user to View and/or Choose
One Value for a Form Field,
out of a Multiple Choice List of Values.


