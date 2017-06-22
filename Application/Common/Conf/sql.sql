
-- 查看所属数据库
select database();

-- 创建数据库
CREATE DATABASE kc;

-- 选择数据库
USE kc ;

-- 创建轩辕币数据表 kc_coin
drop table if exists kc_coin;
create table kc_coin
(
    id int unsigned auto_increment,
    sign varchar(9) not null unique default '',
    uid varchar(12) not null default '',
    phone CHAR(11) NOT NULL DEFAULT '',
    type  enum('认购', '挖币', '自留') not null default '认购',
    status enum('未开采', '钱包中', '交易中','提取中','已提取') not null default '未开采',
    primary key (id),
    index (sign),
    index (uid),
    index (phone)
)charset=utf8;

-- 创建用户表
drop table if exists kc_user;
create table kc_user(
  id int unsigned auto_increment ,
  uid varchar(12) unique not null default '',
  email varchar(255) not null default '',
  password char(40) not null default '',
  salt char(8) not null default '',
  moneyPwd char(40) not null default '',
  realName varchar(25) not null default '',
  phone varchar(11) not null default '',
  mediumer varchar(12) not null default '',
  isMoneyPwd tinyint not null default 0,
  numCoin int unsigned not null default 0,
  available int unsigned not null default 0,
  account decimal(10,2) not null default 0.0,
  usable decimal(10,2) not null default 0.0,
  totalBuyNum int unsigned not null default 0,
  totalSellNum int unsigned not null default 0,
  registerTime int unsigned not null default 0,
  loginTime int unsigned not null default 0,
  isBindPhone tinyint not null default 0,
  isBindEmail tinyint not null default 0,
  isRz tinyint not null default 0,
  region enum('中国','美国','日本') not null default '中国' ,
  company varchar(25) not null default '',
  corporate varchar(12) not null default '',
  address varchar(50) not null default '',
  tel varchar(12) not null default '',
  img1 varchar(255) not null default '',
  img2 varchar(255) not null default '',
  img3 varchar(255) not null default '',
  img4 varchar(255) not null default '',
  img5 varchar(255) not null default '',
  img6 varchar(255) not null default '',
  level enum('个人','综合','特别') not null default '个人',
  place varchar(50) not null default '',
  idType enum('身份证','护照','其他') not null default '身份证',
  idNumber varchar(50) not null default '',
  bank varchar(50) not null default '',
  bankAccount varchar(50) not null default '',
  bankOpening varchar(125) not null default '',
  bankAddres varchar(125) not null default '',
  ip varchar(125) not null default '',
  status tinyint not null default 1,
  primary key (id),
  index (uid),
  index (phone)
)engine=innodb charset=utf8;
-- 给个人用户增加两个状态 冻结 和 注销。冻结权限给到系统管理员，注销是由用户自己申请后，系统管理员审核
-- 把客户注销这个权限删除吧，如果客户想注销可以直接致电我公司，到时候我公司核对无误后再手动帮客户注销
-- 系统管理员要有注销或者冻结的权利
-- 这样的话，客户注册成功后，客户本人无法直接注销，客户可以直接致电我公司，我公司系统管理员审核后，帮其注销（注销后客户无法再登录，注销后客户资料不会删除。但是客户可以再次注册，参与平台交易）

-- 用户登录日志表
drop table if exists kc_login_log;
create table kc_login_log (
  id int unsigned auto_increment,
  uid varchar(12) not null default '',
  phone varchar(11) not null default '',
  time int unsigned not null default 0,
  ip varchar(125) not null default '',
  status enum('成功','失败') not null default '成功',
  primary key (id),
  index (uid),
  index (phone)
)charset = utf8;

-- 用户轩辕币交易表
drop table if exists kc_user_coin;
create table kc_user_coin(
  id int unsigned auto_increment,
  uid varchar(12) not null default '',
  phone CHAR(11) NOT NULL DEFAULT '',
  sign varchar(9) not null default '',
  source enum('收入','支出') not null default '收入',
  remark text not null ,
  primary key (id),
  index (uid),
  index (sign)
)engine=InnoDb charset=utf8;

-- 委托管理表
DROP TABLE IF EXISTS kc_delegate;
CREATE TABLE kc_delegate(
  id int UNSIGNED AUTO_INCREMENT,
  uid VARCHAR(12) NOT NULL DEFAULT '',
  phone CHAR(11) NOT NULL DEFAULT '',
  time INT UNSIGNED NOT NULL DEFAULT 0, -- 委托时间
  hour INT UNSIGNED NOT NULL DEFAULT 0, -- 更新时间
  type ENUM('买入','卖出') NOT NULL DEFAULT '买入',
  price DECIMAL(10,2) NOT NULL DEFAULT 0.0, -- 委托价格
  deal DECIMAL(10,2) NOT NULL DEFAULT 0.0, -- 交易价格
  number INT UNSIGNED NOT NULL DEFAULT 0, -- 委托数量
  balance INT UNSIGNED NOT NULL DEFAULT 0, -- 剩余委托量
  status ENUM('委托中','已成交','取消委托') NOT NULL DEFAULT '委托中',
  fee DECIMAL(10,2) NOT NULL DEFAULT 0.0,
  sign varchar(16) NOT NULL DEFAULT '', -- 委托号
  PRIMARY KEY (id),
  INDEX (uid),
  INDEX (sign)
)CHARSET = utf8 ; -- 委托表也要engine=InnoDb 因为要加锁操作
# ALTER TABLE kc_delegate ADD balance INT UNSIGNED NOT NULL DEFAULT 0;
# ALTER TABLE kc_delegate ADD deal DECIMAL(10,2) NOT NULL DEFAULT 0.0;
# ALTER TABLE kc_delegate ADD hour INT UNSIGNED NOT NULL DEFAULT 0;
# ALTER TABLE kc_delegate ADD sign varchar(16) NOT NULL DEFAULT '';
# ALTER TABLE kc_delegate ADD INDEX index_name ( `sign` );
# ALTER TABLE kc_delegate modify sign varchar(16) NOT NULL DEFAULT '';

-- 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
DROP TABLE IF EXISTS kc_trade;
CREATE TABLE kc_trade(
  id INT UNSIGNED AUTO_INCREMENT,
  delegate_id varchar(16) NOT NULL DEFAULT '',
  uid VARCHAR(12) NOT NULL DEFAULT '',
  phone CHAR(11) NOT NULL DEFAULT '',
  time INT UNSIGNED NOT NULL DEFAULT 0, -- 交易时间
  type ENUM('买入','卖出') NOT NULL DEFAULT '买入',
  delegatePrice DECIMAL(10,2) NOT NULL DEFAULT 0.0,
  tradePrice DECIMAL(10,2) NOT NULL DEFAULT 0.0,
  number INT UNSIGNED NOT NULL DEFAULT 0,
  fee DECIMAL(10,2) NOT NULL DEFAULT 0.0,
  sid VARCHAR(12) NOT NULL DEFAULT '', -- 交易上对方uid
  sphone CHAR(11) NOT NULL DEFAULT '', -- 交易向对方phone
  PRIMARY KEY (id),
  INDEX (delegate_id),
  INDEX (uid),
  INDEX (phone),
  INDEX (sid),
  INDEX (sphone)
)CHARSET = utf8 ;
# ALTER TABLE kc_trade modify delegate_id varchar(16) NOT NULL DEFAULT '';

-- 安全设置记录表
DROP TABLE IF EXISTS kc_safety_record;
CREATE TABLE kc_safety_record(
  id INT UNSIGNED AUTO_INCREMENT,
  uid VARCHAR(12) NOT NULL DEFAULT '',
  phone CHAR(11) NOT NULL DEFAULT '',
  time INT UNSIGNED NOT NULL DEFAULT 0,
  type enum('重置登录密码','设置资金密码','修改手机号','设置邮箱','设置收货地址','实名认证','其他'),
  ip VARCHAR(125) NOT NULL DEFAULT '',
  status enum('成功','失败','其他'),
  PRIMARY KEY (id),
  INDEX (uid),
  INDEX (phone)
)CHARSET = utf8 ;


-- 栏目表
DROP TABLE IF EXISTS kc_column;
CREATE TABLE kc_column(
  id INT UNSIGNED AUTO_INCREMENT,
  name VARCHAR(25) NOT NULL DEFAULT '',
  type ENUM('内容','列表','图文') NOT NULL DEFAULT '列表',
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  time INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(125) NOT NULL DEFAULT '',
  keyWord VARCHAR(125) NOT NULL DEFAULT '',
  description VARCHAR(125) NOT NULL DEFAULT '',
  parent INT UNSIGNED NOT NULL DEFAULT 0,
  level INT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (id)
)CHARSET =utf8;

ALTER TABLE kc_column RENAME kc_category;

-- 文章表
DROP TABLE IF EXISTS kc_article;
CREATE TABLE kc_article(
  id INT UNSIGNED AUTO_INCREMENT,
  name VARCHAR(25) NOT NULL DEFAULT '',
  intro VARCHAR(125) NOT NULL DEFAULT '',
  content TEXT ,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  source VARCHAR(25) NOT NULL DEFAULT '',
  count INT UNSIGNED NOT NULL DEFAULT 0,
  img VARCHAR(125) NOT NULL DEFAULT '',
  file VARCHAR(125) NOT NULL DEFAULT '',
  author VARCHAR(25) NOT NULL DEFAULT '',
  status ENUM('置顶','精华','普通') NOT NULL DEFAULT '置顶',
  created INT UNSIGNED NOT NULL DEFAULT 0,
  updated INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(50) NOT NULL DEFAULT '',
  keyWord VARCHAR(125) NOT NULL DEFAULT '',
  description VARCHAR(125) NOT NULL DEFAULT '',
  parent INT UNSIGNED NOT NULL DEFAULT 0,
  level INT UNSIGNED NOT NULL DEFAULT 0,
  uid VARCHAR(12) NOT NULL DEFAULT '',
  isWork TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
)CHARSET = utf8;

-- 财务明细表表
DROP TABLE IF EXISTS kc_finance;
CREATE TABLE kc_finance(
  id INT UNSIGNED AUTO_INCREMENT,
  uid VARCHAR(12) NOT NULL DEFAULT '',
  phone CHAR(11) NOT NULL DEFAULT '',
  time INT UNSIGNED NOT NULL DEFAULT 0,
  type ENUM('充值','提现','提币','买币','手续费','其他'),
  number INT UNSIGNED NOT NULL DEFAULT 0,
  count INT UNSIGNED NOT NULL DEFAULT 0,
  money DECIMAL(10,2) NOT NULL DEFAULT 0.0,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.0,
  status ENUM('待审核','已到账','已成交','已扣款','其他'),
  remark VARCHAR(255) NOT NULL DEFAULT '',
  audit INT UNSIGNED NOT NULL DEFAULT 0,
  time2 INT UNSIGNED NOT NULL DEFAULT 0,
  time3 INT UNSIGNED NOT NULL DEFAULT 0,
  financeID VARCHAR(25) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX (uid),
  INDEX (phone)
)CHARSET = utf8 ;

-- 铸币厂管理中心记录表
DROP TABLE IF EXISTS kc_mint;
CREATE TABLE kc_mint(
  id INT UNSIGNED AUTO_INCREMENT,
  uid VARCHAR(12) NOT NULL DEFAULT '',
  phone CHAR(11) NOT NULL DEFAULT '',
  appTime INT UNSIGNED NOT NULL DEFAULT 0,
  time INT UNSIGNED NOT NULL DEFAULT 0,
  number INT UNSIGNED NOT NULL DEFAULT 0,
  status INT UNSIGNED NOT NULL DEFAULT 0,
  remark VARCHAR(225) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX(uid),
  INDEX(phone)
)charset=utf8;



