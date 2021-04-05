-- Custom function strSplit()
-- Splits a string in pieces (tokens) at a specified delimiter and returns the requested token
CREATE FUNCTION `strSplit`(str varchar(255), delim varchar(12), tokenNo int)  RETURNS varchar(255) CHARSET utf8
COMMENT 'Splits the string (str) at all delimiters (delim) and returns token number tokenNo'
RETURN replace(SUBSTRING(SUBSTRING_INDEX(str, delim, tokenNo), LENGTH(SUBSTRING_INDEX(str, delim, tokenNo - 1)) + 1), delim, '');
