-- #todo generalize into arrayize_column() fn

-- child_table -> child_tables
alter table tree_relationship
    add column child_tables text[]
    check (array_length(child_tables,1) > 0);

update tree_relationship set child_tables = array[child_table] where child_tables is null;

alter table tree_relationship alter child_tables set not null;

-- #todo rm old column


-- parent_table -> parent_tables
alter table tree_relationship
    add column parent_tables text[]
    check (array_length(parent_tables,1) > 0);

update tree_relationship set parent_tables = array[parent_table] where parent_tables is null;

alter table tree_relationship alter parent_tables set not null;

-- #todo rm old column
