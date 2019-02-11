create table if not exists syncs
(
    id       int auto_increment
        primary key,
    calendar int                                 not null,
    started  timestamp default CURRENT_TIMESTAMP not null
);

create index syncs_calendars_id_fk
    on syncs (calendar);

