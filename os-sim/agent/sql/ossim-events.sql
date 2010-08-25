DROP TABLE IF EXISTS event;
CREATE TABLE event (
    id          BIGINT NOT NULL AUTO_INCREMENT,
    type        ENUM('detector', 'monitor'),
    date        DATETIME NOT NULL,
    sensor      VARCHAR(12),
    interface   VARCHAR(12),
    plugin_id   INT,
    plugin_sid  INT,
    priority    INT DEFAULT 1,
    protocol    VARCHAR(12),
    src_ip      VARCHAR(15),
    src_port    INT,
    dst_ip      VARCHAR(15),
    dst_port    INT,
    username    VARCHAR(255),
    filename    VARCHAR(255),
    log         TEXT,
    data        VARCHAR(255),
    snort_sid   INT,
    snort_cid   INT,
    PRIMARY KEY(id)
);

