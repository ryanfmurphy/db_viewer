Inheritance
===========

DB Viewer supports, but does not require, several methods of Inheritance / Polymorphism
that can be applied to your schema to allow types and subtypes of a certain kind of entity.

## View-based Inheritance

For most SQL flavors, the only feasible ways to get this kind of abstraction
(types and subtypes) is to either

    * use a CREATE VIEW statement to create a SQL view that lets you abstractly define
      a "virtual table", or
    * have a "type" column (duhh)

## PostgreSQL Table Inheritance

If you use PostgreSQL, you have another option: you can use [PostgreSQL](https://www.postgresql.org/)'s
[Inheritance](https://www.postgresql.org/docs/9.1/static/ddl-inherit.html) Feature.
There are wonderful and terrible things about this feature.  I myself still use it heavily in my databases,
though I am moving towards views lately.

