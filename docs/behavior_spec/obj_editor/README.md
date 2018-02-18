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


# Array Fields (PostgreSQL only)

In `db_config.php` you can use the `$fields_w_array_type` config to specify
certain field names to be associated with an array type.  Note that fields can
be any type and will "just work" in Object Editor without any such configs -
`$fields_w_array_type` just enables additional convenience features as are
offered via the configs `$MTM_array_fields_to_not_require_commas` and
`$automatic_curly_braces_for_arrays`.

## `$automatic_curly_braces_for_arrays` config

This is an opt-in convenience feature that provides "syntactic sugar" for array fields.

When this config's value is truthy, then when user is typing into the text box,
they don't have to type the curly braces at the beginning and end of the array value.
The curly braces are added implicitly as needed so that the user can just type the items,
separated by commas.

## `$MTM_array_fields_to_not_require_commas` config

_Note: the following description assumes `$automatic_curly_braces_for_arrays` is also set._

This is an opt-in convenience feature that provides "syntactic sugar" for array fields.

When this config's value is an array that contains a given field name, then when user is
typing into the text box, they don't have to type commas between the items.  For example,
when typing tags into a `tags text[]` field, user could type `tag1 tag2 tag3` instead of
having to type `tag1,tag2,tag3`.  This is especially convenient for mobile phone use,
where the comma is often a screen away in the phone's keyboard.

