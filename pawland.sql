-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 09:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pawland`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_entre`
--

CREATE TABLE `account_entre` (
  `entre_id` int(11) NOT NULL,
  `entre_firstname` varchar(100) DEFAULT NULL,
  `entre_lastname` varchar(100) DEFAULT NULL,
  `entre_email` varchar(100) DEFAULT NULL,
  `entre_password` varchar(100) DEFAULT NULL,
  `entre_phone` varchar(20) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `business_details` text NOT NULL,
  `business_image` varchar(255) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `bussiness_province` varchar(100) DEFAULT NULL,
  `pet_allowed` varchar(200) DEFAULT NULL,
  `pet_size_allowed` varchar(200) DEFAULT NULL,
  `pet_weight_allowed` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_date` datetime NOT NULL,
  `approval_status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_user`
--

CREATE TABLE `account_user` (
  `user_id` int(50) NOT NULL,
  `firstname_account` varchar(100) DEFAULT NULL,
  `lastname_account` varchar(100) DEFAULT NULL,
  `email_account` varchar(100) DEFAULT NULL,
  `password_user` varchar(100) DEFAULT NULL,
  `role_account` varchar(50) DEFAULT NULL,
  `profile_images` varchar(100) DEFAULT NULL,
  `salt_account` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `account_user`
--

INSERT INTO `account_user` (`user_id`, `firstname_account`, `lastname_account`, `email_account`, `password_user`, `role_account`, `profile_images`, `salt_account`) VALUES
(1, 'rey', 'gfjfgrdhy', 'lkhnkldh@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$N2ZGQldpcjFySy52Rmo2Mg$thA49PR/MHwQZx0CZBd2Lx0nExZ22IBhxQS2R6yu84w', NULL, 'default_images_account.jpg', 'bcc32dba3b92726c3aa7a34ffb4476d8369d12f216a001c129974e68b949a31aa396cc213a76e1006bdfe0c0685791b7ed0e207ad7fc726585fc073123bcb0528898d2007d9365c83233ddd722f592935ddab5c033e8ddd7f6c05552bfaccfcf3e95d561cb4929b0def37686b0bd82ede7f2b925'),
(2, 'ploy', 'prae', 'oooo@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cW1ha0x3TjB0UERqOC5SSw$AenNHal9pbVhzj7vVT1Y7vxrKvN+e/Kme6CYjq4ehlM', NULL, 'default_images_account.jpg', 'b7033a58450daeba500e5c669bfa2de36a5026100c235f237245dcfadf8b1a82ab4a7205db52d0c07fc40fbe477c92593a6a5470e501f19c5e0beaba109aaf949516872dda2ad0cfa20c9f1ede1816f53c34b0a83454bf60fb920e183cdfdc0bf49c8df6bf3d4c8bbac70cd138f7b4699455cba871'),
(3, 'rey', 'prae', 'dhfdjh@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$L0ptUWdaa3c0ZlNCdFEwaw$ojyBWKDMw2ZlrEB0yNVBEy3ypq5etle6JFov7UPFxRE', NULL, 'default_images_account.jpg', '75027db7d316b32a40d6d01747cb4fd17100506c6c71ed0c4fd3496b71ebe7b98d8a2250e0d6c7a08b2beb771632c4d29ea4f5ae18db2690be0a70ed95ef5da92623c6f06ecaaec2b68e4908bef11562155fcefe9974cb0da3033897a3767ebbfe'),
(4, 'hfd', 'prae', 'jjj@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$YXNxOHdETU40STZ4amZjZw$siu6G/sN7H2AAx3segK1Ca7paVcQ0I8oo/gHiqkpo9c', NULL, 'default_images_account.jpg', '08e9b4a65a79fc0216213a9707dbe4dc91b7ef837211527ed3652ecc523bc91adad5a7af8d193530173930a6da422b48c0c37d84f45e7732accf212075ca8bd5597e7ee0b58a4feed818d3a23eba2b316f1f1be410c4919ee2ad8eca6287fef209ea'),
(5, 'praewa', 'khonsue', 'prae@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$Q0syU1hUWnNuYnVGeEl0ZA$XixGaecuOTYK5o6rC8/ma807vZ7KZ3zP8nE6sK9q+FI', NULL, 'default_images_account.jpg', '9e6e0366a77ad13ae9fa137d0d6835f12a2d1bed98b5b87246a13b6485d07bfd29681fc73ff56c05fb6b314775d8f6f59a3b06f42bed4d65957bf29d95bd9c99912ec273d99bbea717b4c7bb6c89da6906d85c96ab1182c361b47487edc0a404a6a62859ba8523fe7be8c2'),
(6, 'ploy1', 'ploy1', 'ploy2@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$Z2RMVXl3Wmc5ckdMbHFCNA$xTRGlgj1l6DlomHdjFKMK83gOiCv7ERag6QvlOLehwA', NULL, 'default_images_account.jpg', '996c0d6425f2a392f8eb8569bf30db5f8a97a8f506ca8bfcfccd1d0e9316c4b46d2310e3c6f0d6219b60b4d62477c323dcf2debd879830511a28a23fde205dd522ed3b728fad61646311bc9a5c9b1e8d818c6f649a0b45489b468b3737b9d752d2920d4b277bf4962ef954d5d6bb49937f3e16'),
(7, 'pun', 'pun', 'pun@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$MkIub1BGS0pxWHhNWlBxZg$jnNQIlBaGyLLix0qrEosk99cVCQ2PAOAJDKal046jfQ', 'Admin', 'default_images_account.jpg', '9c76fb266e7ea59b0ef8f86b319d94ea09ce25868816aa9390dd525b38a6cefc8f40efa8c372f361c28d459357a0bb5eb29ac6e49f757c3eee16ea2b0022dbc26ca911ed2c83e17b9a855ea5aa636795d2b04d89b7d076c459b4edbfa0b381441b392944f05ab83dc60a'),
(8, 'Kochakorn', 'Preeprem', 'koc@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$UHFpUEdvaloxRlpkcXNWYg$xBEDUOgvW1MmJiwnG619JrYjk8B60jMm7QIFnpIFEXA', 'Member', 'default_images_account.jpg', '5a6d006580fa84ccfba227d40da8a31456dd65887dfb774178b180d89588dacb85028ea5da81f0f631fcb502ce26c19f25bd8cb2ab8c22b3a43f2fae4a173aa3b7642146d13cd612c16660269a1bd129158a6b0b0be455ef04345bb693b64cd8dc8c134d2f0c8d46c73e9f5511015014f39bbaff'),
(9, 'j0ojo', 'jojo', 'jo@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$UEhNRFhMME9CWnRndHpPSQ$sJyZcm2qTmAQvJ2Ne5EB0KmCnPJxee/ToxQ57vI6rLA', 'member', 'default_images_account.jpg', '6adef531bfa74dd11ec7721579a2a0bb17c52e324cb34165057e0af6bcd941749be9fcf5c0af450f9d17498e9d3f2eb3b2d204bbd7b99d27a1622d67a1de2243d531764c342cbe4fe9a6bd8fbfc309b0f8ce7c0d914e062ff2bd32061bab4302c6625ab7fa5911c127bf410bffb97fda90685d40460def306277a03efcf90b'),
(10, 'ployploy', 'plou', 'ppp@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$WncyaW9zNU95RURGTnFPcg$LiBMWno6xZQW2Bf6mOoBzMaD5JKA5VZJKFw2d4Ux57U', 'member', 'default_images_account.jpg', 'fbcf009d38ac6a945bcfb47203abae0d0a45b82135532beac037cf7df5986be983ba6f180587ced86283de94c06af31d29d25d1047e9068545f928266b6ab82ac4450d193f9277c113274611d5af79b8ac90d6ef7acdab6b399b099afe7c983cd4c5af2de13aeee2f327604687eee8c30a8894c3766c70'),
(11, 'pie', 'pree', 'pie@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$NDJCbFMvQTh3cHhSRjdHWg$QqxBFLXTqsGwVmHSdkeHAWlpOt59vz057QdcV6Ibe00', 'member', 'default_images_account.jpg', '5534d91930caeef9781fff6e881c1137630619634b320749f6b1df85f0fdf82b456f146f1dd909db68bb7ae62e87f28b9b1605a578faa6213df4eea84d45a2a2d81d57513ab3026ed22e3748dd1e474172a8e52f4440d2125f6bd56c33307da66cd055060deb790ecb5aacd1b91a9bb19fece203e09c'),
(12, 'ploy', 'prae', '123@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$aVlmTExLNlc3d25vdVloUA$wEsVK7Og3dzAMC+rkgqt7L6wflWMj24J86cNn/JGyfQ', 'member', 'default_images_account.jpg', '8fe90547a2250ced7715e82f60a479d3cdfa5ce6b7886d5eabba96373ff45701bbe4c2330595d6b621e6dd5769b69d2dfcafa6576104ea2089b942710827cffe43af3ca44154b7206087bf92c64a290af7a099be4f288568dbbaa7b856191574b7f32d828556811715c103'),
(13, 'pun', 'gfjfgrdhy', '1234@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$ZUtWNE5mWHI3NjZOS01nMA$hd79UYu6+SYhASeLnEekVgklev1+NZBNH8kCeHdo6JI', 'member', 'default_images_account.jpg', '88a9a4ac7461446c41b16fdfc624816c36154e3e9759499613c8806be3fdcb6d28733662bbb9e764bd0271821362016cdef43a78fff4aa06fbd35a08221d63765453abafc002f3a87c87dc0b8a858430f9395605795633047925304e267abc25ab88ba38e41ccc88d4df72a40447d8dae10e81e9ba30991d23799582'),
(14, 'fhjfgj', 'gfjfjfgj', 'love@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$NUJSQ0xDU2hhNlVtWGZpQw$oK3+uglMJ74RC6sxy90L6DLOOeGBjFZ3S4QrLetYj24', 'member', 'default_images_account.jpg', 'fb0c17231525eb9ebefa08e095cd43f8d793bb2aa7ffbf2b49c35e00f1a37c2a7d8f887fe14a39f9d4a47712d3aae850e13ef478666048cbae9ef32a9b1a0c3fed4aef06b8a6e5548cac04e1fb55150a7169838f486d8f4c9ab2b95d57a71eb09b'),
(15, 'kochakorn', 'preeprem', 'ployyyy@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$VlpIOXE4SUxGN1NySUtWbw$BHJ9tgekuOOC1YvVlPqNE/rzudFW0xG5tTWmqtw9lg4', 'member', 'default_images_account.jpg', '9be5cc2a6b308f14d6f7bd26d8618e52c5df5f43789fa4641110c9bab3ba0ce296926c6f02b2aeaba6b39436992c32351cac13f25a389e0c0f04e297f5028f9d13ec68994bc5aa707831311da5f41b564abbdeded4cd7dfc9cf379d43ffb6c0e77fc12ea52ec48ee4a914e33f8ff5a'),
(16, 'praewa', 'khonsue', 'praewa22317@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$Y2ZNTHVXTG02bW05WDduSQ$IIZ2VTQzLiNaS5GJe5QIpKABub/H/4z2rFPPcx3Ah6k', 'member', 'default_images_account.jpg', '560bf26dfbd16f8e3dd28fb25bf99930ce5ab8b389da4a6bfb0f843ca44b2ab17279b865b4b8e731323481c090e85e4bed9fc5035d673e8f8e19e29a7fc55b5ed1d7bf30c2d752c21bfe583c2c7814127a4e721c0ee84424d18fa84b6e2fa8bbf353d0e12119c7f1c6dc23c1e502b95bf65ee02e3f'),
(17, 'ออแกน', 'จตุรวิท', 'organ@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$TmpQUEtZa0NkWTZYdjlqWA$mevHii/E/ayS2RRpaiWIDACP2uBx+TDzgyoTf6CfVwQ', 'member', 'default_images_account.jpg', '332b2f0b89feffd3e002f34839cac106be3957c3d5dcb5574964eb8cd2a291a57673dcb351c56b8e0d27232f268e91a12947db5218e5f6dd909d15d8176d23f8bdce8453001b503a756a8f1e3116eb68fa3c88464d824a10b44c1fe9430921f01fb37678f75953efb2305778f5ddcd83'),
(18, 'pun', 'kongklom', 'punlnwza@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$M2NUV1ZVc3Iybmh4VE4zdQ$AwgJWneNFiOae8a6O2SxMp/gXXtEoddt3iejISn3NWo', 'member', 'default_images_account.jpg', '7ab944e768b445d9743c722a7bfb7a54d33da3cc3d342c70f74884ebaf1f390f4bffe692150da26609169eba8f2b57a4d5c11d8ce9f9fd23be261ca942eed5a452dbf65a977d1935db06eae33f25485a9aa96cdeaf038519599b9a4cb3bd9253837b54cc9c6ea3d6bf12cfa49de34f34f8abc7608a187ce20192a7e4f6df26d7'),
(19, 'พลอย', 'แพรวา', 'ploy1234@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$dVFzaUwudGlPL2N2R2ZneA$HgIv1FR4f59cShAy45FsN8iELq2Jq2ZZU+A0pVB1DEY', 'member', 'default_images_account.jpg', '3dacd8ba612858f8e58f8a077c365166631c6e1a6b118762e266dd6eab297167166f755edd47871e6377ae23de53b1614d4f08f44adff969d8a20c9fc33d3cab82aa6376d3aae2cda4308b44d33a3e336c53bb26de262689933b129fb640109d3ba9231ce6b99023552fc3e67ddc4c99b59a3423df44e999f7dc71'),
(20, 'praewa', 'khonsue', 'ploykck.p@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$QnhUc0suUU9NQ3BVci5VUQ$otI9TCcWY1DT9tlWaCGCQO7nUd02xN46z0hBB1Lz7Po', 'member', 'default_images_account.jpg', 'e2ce41aa6d8a5fe6d14dde200ae468ebfe64c795f95991ff8b7952ac58af0a4e02d2aaccccc6b2017a2b5f95d2b8c3821e1e89d378eac89e1f9bdf816ed67b4ea28adfca6bbef4bb8dd8f0278423fc2b21c9f71e3526115d6191d4d245703b2aa06047428611'),
(21, 'jjjj', 'jjjjj', 'jjj1@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$Lmo0TTZZeHcxOHFIdkNrTA$fuUbxbVgeDle36Lt9BowurcN3KE0Rc6gna70UYexpys', 'member', 'default_images_account.jpg', 'd586f577e2798984430fca094c419a9c6589b6c33de9582e52c06b2b30e96cda94bfd3f80e4e36c0e8b4a167cd44e35003382b73694e61dad7397e66df81844cf89aa42656f856222111c395af07105390f9c180c040fab979ca1d38763153015efa76fd6847'),
(22, 'praewa', 'khonsue', 'praewa1101@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$SmhxYU5IR2FEdHdCeUpQUQ$Vunqlx3ArCuPwCaPD7s17kQbfmxU1wXQup7fUTv6U9E', 'member', 'uploads/profiles/profile_22_1775159778.jpg', '116b8a8871c32a3709d02a6cee1a80e17e37411355ce71bcfd27afcbbf873a0e4f9a703852f57d6efb680f623b3d3706c46825061ed93f48bc68553b99348510726a470f7450d0fc545fb1b85de05a9c28e96eb8f16cb8247149e6a3534e5e7592c40f43558d34'),
(23, 'gggg', 'ggg', 'sss@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cFdBQ05vZFBjYkFoby9FaQ$Q19l+ChGiyFlq4LknAAZbqkAHcn5Hf3zafitP3kl4ZQ', 'member', 'default_images_account.jpg', '1b29138c47d4d55c303773d2c47618a2c9e5dcb312b7172dcca1b98a42ea1041fb73bc260a5b41c96b8a21d3121eb3966103e4b59625259e9463fb35457f2b236c35d886f1652b967394444d04c22062a15e91d10ffad0b8fcf2a3fcba827460b1716690854c2dacb01eec8af602d46a8622e4'),
(24, 'กชกร', 'ปรีเปรม', 'ploykck.p2@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$NXo1MkE1OUpnV1FhM1BPQg$JJvU/4dPBPVsJ9URG+VsyvBQfEGXDE2y9LEsS3vJpTY', 'member', 'default_images_account.jpg', 'ab5aa99e7f356b7cdf8cf106b462e64394af00cc664d79a99c777c43219aeb5ab2bf54fe4ac1807930c53b19e13775e5a3fe7a61f5259b8f244a185ff04cb445d451ad389326681dcf12bf4ad989d28902ae5ebfa2fbea09acee7fedc8a2338b01ec1250b7ce5f647cb66c17f44dea46b1'),
(25, 'admin', '123', 'admin123@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$V0Q2SGlOajEuNnNHTHhUZA$ExkrKs/kaNb1TKYGNZ2XWLwxdiv0x9KVAHOOfGMdhXA', 'Admin', 'default_images_account.jpg', 'cd4b318c24b9d5d475724758b70672439893658f4996edab19d8e1f8b5ab47aa8e5ce10ab977d52c8cd069681e2be9648d013c839467730e040d8e303131bef44378778d4661d02ae0a27375c3272ec1106b36c1ebc61bac8a8ec1c19645bf31ca96f87bcd1b9970289805f8c0933316459b');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `admin_username` varchar(100) DEFAULT NULL,
  `admin_email` varchar(100) DEFAULT NULL,
  `admin_password` varchar(255) DEFAULT NULL,
  `admin_created_at` datetime NOT NULL,
  `admin_last_login` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `admin_username`, `admin_email`, `admin_password`, `admin_created_at`, `admin_last_login`) VALUES
(1, 'pun', 'pun@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSsUFxq2e', '2026-04-29 14:09:27', '2026-04-29 14:09:27'),
(2, 'admin123', 'admin123@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSsUFxq2e', '2026-04-29 14:13:45', '2026-04-29 14:13:45'),
(3, 'admin123', 'admin123@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSsUFxq2e', '2026-04-29 14:15:36', '2026-04-29 14:15:36');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id_bill` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('ยังไม่จ่าย','จ่ายแล้ว') NOT NULL DEFAULT 'ยังไม่จ่าย',
  `slip_image` varchar(255) DEFAULT NULL,
  `is_notifiled` tinyint(1) NOT NULL DEFAULT 0,
  `bill_templat_id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_templates`
--

CREATE TABLE `bill_templates` (
  `bill_templat_id` int(11) NOT NULL,
  `bill_title` varchar(100) NOT NULL,
  `bill_amount` decimal(10,2) NOT NULL,
  `every_month_day` int(11) NOT NULL,
  `remind_before` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_cate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_license`
--

CREATE TABLE `business_license` (
  `license_id` int(11) NOT NULL,
  `entre_id` int(11) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_image` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_cate` int(11) NOT NULL,
  `name_cate` enum('ค่าน้ำ','ค่าไฟ','ค่าสตรีมมิ่ง','ค่าอินเทอร์เน็ต') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_cate`, `name_cate`) VALUES
(1, 'ค่าน้ำ'),
(2, 'ค่าไฟ'),
(3, 'ค่าสตรีมมิ่ง'),
(4, 'ค่าอินเทอร์เน็ต');

-- --------------------------------------------------------

--
-- Table structure for table `favorite`
--

CREATE TABLE `favorite` (
  `favorite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `place_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `favorite`
--

INSERT INTO `favorite` (`favorite_id`, `user_id`, `place_id`) VALUES
(1, 24, 16);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `fav_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `place_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `pet_name` varchar(100) DEFAULT NULL,
  `pet_type` enum('สุนัข','แมว','กระต่าย','หนูแฮมสเตอร์','กระรอก','นกแก้ว','นกคอกคาเทล','เต่าญี่ปุ่น','งู','กิ้งก่า') DEFAULT NULL,
  `pet_breed` varchar(100) DEFAULT NULL,
  `pet_gender` enum('male','female') DEFAULT NULL,
  `pet_weight` int(11) DEFAULT NULL,
  `pet_old` date DEFAULT NULL,
  `pet_birthday` date DEFAULT NULL,
  `pet_behaviors` varchar(200) DEFAULT NULL,
  `pet_image` varchar(255) NOT NULL,
  `flea_tick` date DEFAULT NULL,
  `microship_number` varchar(30) NOT NULL,
  `microship_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `user_id`, `pet_name`, `pet_type`, `pet_breed`, `pet_gender`, `pet_weight`, `pet_old`, `pet_birthday`, `pet_behaviors`, `pet_image`, `flea_tick`, `microship_number`, `microship_date`) VALUES
(1, 22, 'ไลอ้อน', '', 'สก๊อตทิช โฟลด์', 'male', 4, '0000-00-00', NULL, 'ขี้กลัว, หลับง่าย', '', NULL, '', NULL),
(2, 22, 'น้ำตาล', 'สุนัข', 'ซามอยด์', 'male', 9, '0000-00-00', NULL, 'ไม่เป็นมิตร, ชอบพื้นที่กว้าง', 'uploads/pets/pet_2_1775158050.jpg', NULL, '', NULL),
(3, 16, 'fff', 'แมว', 'เปอร์เซีย', 'female', 7, '0000-00-00', NULL, '', 'uploads/pets/pet_3_1775255085.jpg', '2026-02-03', '764 010000000001', '2025-07-18'),
(4, 24, 'ฟูฟู', 'แมว', 'แร็กดอล', 'male', 6, '0000-00-00', NULL, '', 'uploads/pets/pet_4_1775273134.jpg', '2026-02-12', '764 010000000001', '2025-10-22');

-- --------------------------------------------------------

--
-- Table structure for table `places`
--

CREATE TABLE `places` (
  `place_id` int(11) NOT NULL,
  `place_name` varchar(200) DEFAULT NULL,
  `category` enum('โรงแรม','คาเฟ่','ร้านอาหาร','อาบน้ำ ตัดขน','โรงพยาบาลสัตว์') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `latitude` decimal(15,6) NOT NULL,
  `longitude` decimal(15,6) NOT NULL,
  `pet_size_allowed` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `place_image` varchar(200) DEFAULT NULL,
  `pet_type_allowed` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `places`
--

INSERT INTO `places` (`place_id`, `place_name`, `category`, `address`, `province`, `latitude`, `longitude`, `pet_size_allowed`, `description`, `place_image`, `pet_type_allowed`) VALUES
(1, 'The Kaze 34 Hotel and Serviced Residence', 'โรงแรม', '66 สุขุมวิท ซอย34 ถนน สุขุมวิท แขวงคลองตัน เขตคลองเตย (ใกล้ บีทีเอส ทองหล่อ) กทม., สุขุมวิท, กรุงเทพ, ไทย, 10110', 'กรุงเทพมหานคร\r\n\r\n', 13.723778, 100.571993, NULL, NULL, 'https://petfriendlythailand.com/wp-content/uploads/2021/08/22050v000000jl25kE4D9_Z_1080_808_R5_D-1024x682.jpg', 'สุนัข (หมา), แมว'),
(2, 'Kimpton Maa-Lai Bangkok (by IHG)', 'โรงแรม', '78 ซอยต้นสน แขวงลุมพินี เขตปทุมวัน กรุงเทพมหานคร 10330 ประเทศไทย', 'กรุงเทพมหานคร', 13.743200, 100.549800, NULL, 'ไม่คิดค่าธรรมเนียม มีชามอาหาร น้ำให้ในห้อง', 'https://mushroomtravelpage.b-cdn.net/wp-content/uploads/2020/08/1-4-1024x768.jpg', 'สุนัข (หมา), แมว   (ทุกขนาด)'),
(3, 'The Quartier Hotel Phromphong', 'โรงแรม', '413 ซอยสุขุมวิท 49/11, คลองตันเหนือ, วัฒนา, กรุงเทพฯ 10110', 'กรุงเทพมหานคร', 13.737176, 100.576155, 'อนุญาตนำสัตว์ได้ 2 ตัว (ไม่เกิน 30 กก. ต่อสัตว์) \r\nค่าธรรมเนียม 500 บ. ต่อสัตว์/คืน', NULL, 'https://ak-d.tripcdn.com/images/200t1c000001dg6ar4A8E_R_960_660_R5_D.jpg', 'สุนัข (หมา), แมว'),
(4, 'Happy Bird’s Day', 'คาเฟ่', 'ซอยสุขุมวิท 63 แขวงพระโขนงเหนือ เขตวัฒนา กรุงเทพฯ 10110 ประเทศไทย', 'กรุงเทพมหานคร', 13.726299, 100.588080, NULL, 'ร้านคาเฟ่แนวสัตว์รวมหลายชนิด\r\n(นกกาชาด, เป็ด, กระต่าย, หนู\r\nตะเภา ฯลฯ) เปิดรับสุนัข/แมวเข้าได', 'https://themomentum.co/wp-content/uploads/2022/12/web_%E0%B8%84%E0%B8%B2%E0%B9%80%E0%B8%9F%E0%B9%88%E0%B8%99%E0%B8%81-1280x720.jpg', 'สุนัข (หมา), แมว, นก, กระต่าย, หนู ตะเภา'),
(5, 'Caturday Cat Café', 'คาเฟ่', '10400, 89/70 ถนนพญาไท แขวงถนนเพชรบุรี เขตราชเทวี กรุงเทพมหานคร 10400 ประเทศไทย', 'กรุงเทพมหานคร', 13.751589, 100.532863, NULL, 'คาเฟ่แมวนั่งเล่น เน้นสายพันธุ ์หน้า\r\nตลก (มีค่าบริการเข้าชม/อาหาร)', 'https://www.hypeandstuff.com/wp-content/uploads/2019/09/CaturdayCafe-1.jpg', 'แมว'),
(6, 'Dog in Town (Ekkamai)', 'คาเฟ่', '16, ซอยเอกมัย 6 คลองตันเหนือ วัฒนา กรุงเทพฯ 10110 ประเทศไทย', 'กรุงเทพมหานคร', 13.726244, 100.588346, 'สุนัข (พันธุ ์ใหญ่ เช่น malamute, corgi)', 'คาเฟ่สุนัขใหญ่ มีพื ้นที่ให้ถ่ายรูป\r\nสุนัขปล่อยเล่นในสวนบ่อยๆ', 'https://gohsomewhere.com/wp-content/uploads/2023/12/dog-in-town-1024x768.jpg', 'สุนัข (หมา)'),
(7, 'Little Zoo Café', 'คาเฟ่', '486 ถนนอ่อนนุช แขวงสวนหลวง เขตสวนหลวง กรุงเทพมหานคร 10250 ประเทศไทย', 'กรุงเทพมหานคร', 13.711649, 100.608643, NULL, 'คาเฟ่สัตว์เอ็กโซติก พบนากทะเล\r\nจิ้งจอกฟีนิกซ์ แรคคูน ฯลฯ (มีค่า\r\nบริการเข้างานและเครื ่องดื ่ม)', 'https://th.bing.com/th/id/R.4e56cef352d256d240feecc02b16089e?rik=47Kzey5jNZpp9g&pid=ImgRaw&r=0', 'สุนัข (หมา), แมว, สัตว์แปลกเช่น นากทะเล (meerkat)'),
(8, 'Cross Vibe Chiang Mai Decem', 'โรงแรม', '10/18 หมู่ 2 ถนนซุปเปอร์ไฮเวย์ ช้างเผือก อ.เมือง เชียงใหม่ 50300', 'เชียงใหม่', 18.804994, 98.969325, 'สุนัข  (≤10 กก. ในห้อง\r\nที่กำหนด), แมว', 'ไม่คิดค่าธรรมเนียม (สัตว์ตัวที่สองอาจรับได้โดยแจ้งล่วงหน้าและอาจมีค่าธรรมเนียมเพิ่มเติม)', 'https://lirp.cdn-website.com/3cc64e5f/dms3rep/multi/opt/Cross+Vibe+Chiang+Mai+Decem+-+Deluxe+Room+-+Double+Bed+-+Funku+hotel+in+Nimman-1920w.jpg', 'สุนัข (หมา) (≤10 กก. ในห้อง ที่กำหนด), แมว'),
(10, 'The Twenty Lodge', 'โรงแรม', '8/3 ซอย 3 ถ.สิงหราช ต.ศรีภูมิ อ.เมืองเชียงใหม่, Si Phum, 50200 เชียงใหม่', 'เชียงใหม่', 18.798700, 98.991000, 'สุนัข (หมา), แมว ทุกขนาด', 'ไม่มีค่าธรรมเนียมสาหรับสัตว์เลี้ยง มี\r\nชามอาหาร–น้าให้ในห้อง', 'https://img.wongnai.com/p/1920x0/2018/03/27/744565af24b149879f0660e98164aa1c.jpg', 'สุนัข (หมา), แมว ทุกขนาด'),
(12, 'Anantasila Beach Resort Hua Hin', 'โรงแรม', '33, ซอยอ่าวหัวดอน 7 หนองแก อำเภอหัวหิน จังหวัดประจวบคีรีขันธ์ 77110', 'ประจวบคีรีขันธ์ (หัวหิน – ปราณบุรี)', 12.528100, 99.957400, 'สุนัข (≤10 กก.), แมว (ขออนุญาต)', 'อนุญาต 2 สัตว์เลี้ยงต่อห้อง (สุนัขตัวใหญ่)\r\nค่าบริการ 500 บ./ตัว/คืน (วางประกัน 1,000 บ./คืน) แมวเข้าพักได้ตามคำขอ', 'https://www.anantasila.com/backend/img/202405191405881222.JPG', 'สุนัข (หมา.), แมว'),
(13, 'Baan TuaYen x The Dogs Space', 'คาเฟ่', '33, ซอยอ่าวหัวดอน 7 หนองแก อำเภอหัวหิน จังหวัดประจวบคีรีขันธ์ 77110', 'ประจวบคีรีขันธ์ (หัวหิน – ปราณบุรี)', 12.528100, 99.957400, 'สุนัข', 'Dog park ขนาดเล็ก-คาเฟ่สวนหลังบ้าน รับ\r\nสุนัขวิ่งเล่นในพื้นที่รั้วได้ (เปิดทุกวัน 9:30 - 18:00 น.)', 'https://api.designconnext.com/asset/image/portfolio-workpiece-watermark/b45ad740-2941-11ef-b06a-0d1a8a7b610e/eyJyZXNpemUiOnsid2lkdGgiOjkwMH19/77593f70cfddccaa34767e80973d1452.jpg', 'สุนัข (หมา.)'),
(14, 'MAY’s (Pattaya)', 'ร้านอาหาร', '315/74 หมู่ 12 ถนนเทพประสิทธิ์\r\nหนองปรือ บางละมุง\r\nชลบุรี 20150', 'ชลบุรี (พัทยา)', 12.935600, 100.882600, ' สุนัข มีบริเวณโต๊ะนอกอาคารให้สุนัขเข้าได้', 'ร้านอาหารไทย-สากล มีบริเวณโต๊ะนอกอาคารให้สุนัขเข้า\r\nได', 'https://tse4.mm.bing.net/th/id/OIP.e6txPYfYaxMW6zEYtkmXPwHaJQ?rs=1&pid=ImgDetMain&o=7&rm=3', 'สุนัข (หมา)'),
(16, 'Outback Sports Bar & Restaurant', 'ร้านอาหาร', '27/6 ม.4 ตำบลหนองปรือ, บางละมุง 20150 ไทย', 'ชลบุรี', 12.935600, 100.882600, NULL, 'ร้านอาหาร-บาร์สไตล์ออสซี่ มีพื้นที่ด้านนอกให้พาน้อง\r\nหมาไปนั่งได้', 'https://tse3.mm.bing.net/th/id/OIP.oKUMoqqCEFsA9Iun01X7nAHaE7?rs=1&pid=ImgDetMain&o=7&rm=3', 'สุนัข (หมา)'),
(18, 'โรงพยาบาลสัตว์ทองหล่อ', 'โรงพยาบาลสัตว์', '80 ถนน เพชรพระราม แขวงบางกะปิ เขตห้วยขวาง กรุงเทพมหานคร 10310', 'กรุงเทพมหานคร', 13.730800, 100.519200, NULL, 'เปิดให้บริการดูแลรักษาสัตว์เลี้ยงทุกชนิด เครื่องมือทางการแพทย์ที่ทันสมัย มีบริการครบวงจร', 'https://thonglorpet.com/_content_images/branch/B0003.jpg?vs=20250317222458', 'สุนัข (หมา), แมว'),
(19, 'โรงพยาบาลสัตว์สวนหลวง ', 'โรงพยาบาลสัตว์', '267 ซอย เฉลิมพระเกียรติ ร. 9 แขวงหนองบอน เขตประเวศ กรุงเทพมหานคร 10250', 'กรุงเทพมหานคร', 13.695600, 100.634800, NULL, 'ทีมงานบริการดี และทีมสัตวแพทย์แจ้งถึงอาการของสัตว์เลี้ยงได้อย่างชัดเจน บริการรักษาดูแลรักษาสัตว์ ผ่าตัด, อุบัติเหตุฉุกเฉิน ตลอด 24 ชั่วโมง', 'https://lh3.googleusercontent.com/p/AF1QipNKan8EE1gaA-WYtzAmpBlEVfSo4AjoSaQHhqvY=s680-w680-h510', 'สุนัข (หมา), แมว');

-- --------------------------------------------------------

--
-- Table structure for table `travel_plan`
--

CREATE TABLE `travel_plan` (
  `plan_id` int(11) NOT NULL,
  `trip_name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_plan`
--

INSERT INTO `travel_plan` (`plan_id`, `trip_name`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'gogooooo', 21, '2026-03-30 05:35:31', '2026-03-30 05:35:31'),
(2, 'gogooooo', 21, '2026-03-30 05:35:33', '2026-03-30 05:35:33'),
(3, 'gogooooo', 21, '2026-03-30 05:35:53', '2026-03-30 05:35:53'),
(4, 'gogooooo', 21, '2026-03-30 05:35:54', '2026-03-30 05:35:54'),
(5, 'หรรษาาาา', 21, '2026-03-30 05:36:29', '2026-03-30 05:36:29'),
(6, 'หรรษาาาา', 21, '2026-03-30 05:40:45', '2026-03-30 05:40:45'),
(7, 'เฮฮาา', 22, '2026-03-30 16:16:02', '2026-03-30 16:16:02'),
(8, 'เฮฮาา', 22, '2026-03-30 16:16:10', '2026-03-30 16:16:10'),
(9, 'ไปกันน', 22, '2026-03-30 16:19:30', '2026-03-30 16:19:30'),
(11, 'letgo', 15, '2026-04-02 23:57:18', '2026-04-02 23:57:18'),
(13, 'lolol', 22, '2026-04-03 00:47:04', '2026-04-03 00:47:04'),
(14, 'มาเถอะ', 22, '2026-04-03 00:53:43', '2026-04-03 00:53:43'),
(15, 'มาเถอะ', 22, '2026-04-03 00:53:47', '2026-04-03 00:53:47'),
(16, 'ออ', 22, '2026-04-03 00:55:01', '2026-04-03 01:07:13'),
(17, 'หหห', 16, '2026-04-04 04:39:27', '2026-04-04 04:39:27'),
(18, 'สนุกมาก', 16, '2026-04-04 09:04:13', '2026-04-04 09:04:13');

-- --------------------------------------------------------

--
-- Table structure for table `travel_plan_place`
--

CREATE TABLE `travel_plan_place` (
  `plan_id` int(11) NOT NULL,
  `trip_name` varchar(255) DEFAULT NULL,
  `place_id` int(11) NOT NULL,
  `place_name` varchar(255) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `order_num` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `travel_plan_place`
--

INSERT INTO `travel_plan_place` (`plan_id`, `trip_name`, `place_id`, `place_name`, `visit_date`, `check_in`, `check_out`, `order_num`) VALUES
(1, NULL, 16, 'Outback Sports Bar & Restaurant', '2026-04-16', '2026-04-16', '2026-04-16', 1),
(2, NULL, 16, 'Outback Sports Bar & Restaurant', '2026-04-16', '2026-04-16', '2026-04-16', 1),
(3, NULL, 16, 'Outback Sports Bar & Restaurant', '2026-04-16', '2026-04-16', '2026-04-16', 1),
(4, NULL, 16, 'Outback Sports Bar & Restaurant', '2026-04-16', '2026-04-16', '2026-04-16', 1),
(5, NULL, 10, 'The Twenty Lodge', '2026-04-10', '2026-04-10', '2026-04-10', 1),
(6, NULL, 10, 'The Twenty Lodge', '2026-04-10', '2026-04-10', '2026-04-10', 1),
(7, NULL, 3, 'The Quartier Hotel Phromphong', '2026-04-08', '2026-04-08', '2026-04-10', 1),
(8, NULL, 3, 'The Quartier Hotel Phromphong', '2026-04-08', '2026-04-08', '2026-04-10', 1),
(9, NULL, 5, 'Caturday Cat Café', '2026-04-15', '2026-04-15', '2026-04-15', 1),
(11, NULL, 12, 'Anantasila Beach Resort Hua Hin', '2026-04-18', '2026-04-18', '2026-04-20', 1),
(13, NULL, 12, 'Anantasila Beach Resort Hua Hin', '2026-04-18', '2026-04-18', '2026-04-20', 1),
(14, NULL, 14, 'MAY’s (Pattaya)', '2026-04-14', '2026-04-14', '2026-04-14', 1),
(14, NULL, 5, 'Caturday Cat Café', '2026-04-14', '2026-04-14', '2026-04-14', 2),
(15, NULL, 14, 'MAY’s (Pattaya)', '2026-04-14', '2026-04-14', '2026-04-14', 1),
(15, NULL, 5, 'Caturday Cat Café', '2026-04-14', '2026-04-14', '2026-04-14', 2),
(16, NULL, 14, 'MAY’s (Pattaya)', '2026-04-17', '2026-04-17', '2026-04-17', 1),
(16, NULL, 5, 'Caturday Cat Café', '2026-04-14', '2026-04-14', '2026-04-14', 2),
(17, NULL, 16, 'Outback Sports Bar & Restaurant', '2026-04-04', '2026-04-04', '2026-04-06', 1),
(18, NULL, 6, 'Dog in Town (Ekkamai)', '2026-04-22', '2026-04-22', '2026-04-22', 1),
(18, NULL, 18, 'โรงพยาบาลสัตว์ทองหล่อ', '2026-04-23', '2026-04-23', '2026-04-23', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_entre`
--
ALTER TABLE `account_entre`
  ADD PRIMARY KEY (`entre_id`),
  ADD UNIQUE KEY `entre_email` (`entre_email`);

--
-- Indexes for table `account_user`
--
ALTER TABLE `account_user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email_unique` (`email_account`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id_bill`),
  ADD KEY `bill_templat_id` (`bill_templat_id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `bill_templates`
--
ALTER TABLE `bill_templates`
  ADD PRIMARY KEY (`bill_templat_id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_cate` (`id_cate`);

--
-- Indexes for table `business_license`
--
ALTER TABLE `business_license`
  ADD PRIMARY KEY (`license_id`),
  ADD KEY `entre_id` (`entre_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_cate`);

--
-- Indexes for table `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`favorite_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `place_id` (`place_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`fav_id`),
  ADD UNIQUE KEY `uq_user_place` (`user_id`,`place_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`place_id`);

--
-- Indexes for table `travel_plan`
--
ALTER TABLE `travel_plan`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `travel_plan_place`
--
ALTER TABLE `travel_plan_place`
  ADD PRIMARY KEY (`plan_id`,`order_num`),
  ADD KEY `place_id` (`place_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_entre`
--
ALTER TABLE `account_entre`
  MODIFY `entre_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `account_user`
--
ALTER TABLE `account_user`
  MODIFY `user_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id_bill` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_templates`
--
ALTER TABLE `bill_templates`
  MODIFY `bill_templat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_license`
--
ALTER TABLE `business_license`
  MODIFY `license_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_cate` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `favorite`
--
ALTER TABLE `favorite`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `fav_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `places`
--
ALTER TABLE `places`
  MODIFY `place_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `travel_plan`
--
ALTER TABLE `travel_plan`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`bill_templat_id`) REFERENCES `bill_templates` (`bill_templat_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `bill_templates`
--
ALTER TABLE `bill_templates`
  ADD CONSTRAINT `bill_templates_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `bill_templates_ibfk_2` FOREIGN KEY (`id_cate`) REFERENCES `categories` (`id_cate`);

--
-- Constraints for table `business_license`
--
ALTER TABLE `business_license`
  ADD CONSTRAINT `business_license_ibfk_1` FOREIGN KEY (`entre_id`) REFERENCES `account_entre` (`entre_id`);

--
-- Constraints for table `favorite`
--
ALTER TABLE `favorite`
  ADD CONSTRAINT `favorite_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `account_user` (`user_id`),
  ADD CONSTRAINT `favorite_ibfk_2` FOREIGN KEY (`place_id`) REFERENCES `places` (`place_id`);

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `account_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `travel_plan_place`
--
ALTER TABLE `travel_plan_place`
  ADD CONSTRAINT `travel_plan_place_ibfk_1` FOREIGN KEY (`place_id`) REFERENCES `places` (`place_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
