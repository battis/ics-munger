create table if not exists calendars
(
    id       int auto_increment
        primary key,
    name     varchar(1000)                       not null,
    created  timestamp default CURRENT_TIMESTAMP not null,
    modified timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
    constraint calendars_name_uindex
        unique (name)
);

