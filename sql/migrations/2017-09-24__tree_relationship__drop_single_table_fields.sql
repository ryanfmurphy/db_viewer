-- 2017-09-24
alter table tree_relationship drop column child_table;
alter table tree_relationship drop column parent_table;

-- rm overbearing constraint
alter table tree_relationship drop constraint tree_relationship_parent_tables_check;
alter table tree_relationship drop constraint tree_relationship_child_tables_check;
alter table tree_relationship alter parent_tables drop not null;
alter table tree_relationship alter child_tables drop not null;
