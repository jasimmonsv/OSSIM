-- USB Udev Hardware detection
--
-- plugin_id: 1640
--

DELETE FROM plugin WHERE id = "1640";
DELETE FROM plugin_sid where plugin_id = "1640";

INSERT INTO plugin (id, type, name, description) VALUES (1640, 1, 'usbudev', 'USB Udev Hardware detection');

INSERT INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1640, 1, 'Usbudev: An USB Device was added to the system' , 3, 5);
INSERT INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1640, 2, 'Usbudev: An USB Device was removed from the system' , 3, 5);
