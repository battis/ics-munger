create table if not exists events
(
    id       int auto_increment
        primary key,
    calendar int                                 not null,
    uid      text                                not null,
    vevent   longtext                            not null,
    created  timestamp default CURRENT_TIMESTAMP not null,
    modified timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
    sync     int                                 not null
);

create index events_calendars_id_fk
    on events (calendar);

create index events_syncs_id_fk
    on events (sync);

