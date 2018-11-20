<?php
require "connect.php";
require "functions.php";
session_start();

if(isset($_GET['section'])) {
    $section = $_GET['section'];
} else {
    $section = "index";
}

switch($section) {
    case "index":
        $items = get_items();
        $tree = build_tree_index($items,0);
        include_once "./tpl/index.phtml";
    break;

    case "auth":
        $errors = [];
        if(count($_POST)) {
            if(auth_check($_POST['email'], md5($_POST['password']))) {
                setcookie('email', $_POST['email'], time()+3600*24*30, '/');
                setcookie('password', md5($_POST['password']), time()+3600*24*30, '/');
                header("Location: index.php?section=admin");
                exit;
            } else {
                $errors[] = "Данные введены неверно";
            }
        }
        include_once "./tpl/auth.phtml";
    break;
    case "logout":
        setcookie('email', "", -1, '/');
        setcookie('password', "", -1, '/');
        header("Location: index.php?section=index");
        exit;
    break;

    case "admin":
        //******************************** AUTH
        if(!auth_check(@$_COOKIE['email'], @$_COOKIE['password'])) {
            header("Location: index.php?section=auth", true, 301);
            exit;
        }
        //******************************** FILL
        $id = 0;
        if(isset($_GET['id']) && $_GET['id'] != 0) {
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            if($stmt->rowCount()) {
                extract($stmt->fetch());
            }
        }

        //******************************** DELETE
        if(isset($_GET['del'])) {
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? OR parent_id = ?");
            $resp = $stmt->execute([$_GET['del'],$_GET['del']]);
            if($resp) {
                $_SESSION['flash'] = "item succesfully deleted";
                header("Location: index.php?section=admin", true, 301);
                exit;
            }
        }

        //******************************** INSERT/UPDATE
        if(count($_POST)) {
            extract($_POST);
            $errors = [];
            if(empty(trim($name))) {
                $errors[] = "Please, enter name";
            } else {
                if(mb_strlen($name) > 255) {
                    $errors[] = "Max characters of filed name is 255";
                }
            }
            if(!count($errors)) {
                if(empty($id)) {
                    $stmt = $pdo->prepare("INSERT INTO items (name, description, parent_id)
                                    VALUES (?,?,?)");
                    $resp = $stmt->execute([$name, $description, $parent_id]);
                    if($resp) {
                        $_SESSION['flash'] = "item succesfully added";
                        header("Location: index.php?section=admin", true, 301);
                        exit;
                    }
                } else {
                    $stmt = $pdo->prepare("SELECT parent_id FROM items WHERE id = ?");
                    $stmt->execute([$id]);
                    if($stmt->rowCount()) {
                        $old_parent_id = $stmt->fetchColumn();
                        if($parent_id != $old_parent_id) {
                            $items = get_items();
                            $children_ids_all = get_children_ids_all($items,$id);
                            if(in_array($parent_id, array_column($children_ids_all, "id"))) {
                                $children_ids =  array_column(get_children_ids($items, $id), "id");
                                $stmt = $pdo->prepare("UPDATE items SET parent_id = ? WHERE id = ?");
                                foreach($children_ids as $children_id) {
                                    $stmt->execute([$old_parent_id, $children_id]);
                                }
                            }
                        }
                        $stmt = $pdo->prepare("UPDATE items SET name = ?, description = ?, parent_id = ? WHERE id = ?");
                        $resp = $stmt->execute([$name, $description, $parent_id, $id]);
                        if($resp) {
                            $_SESSION['flash'] = "item succesfully update";
                            header("Location: index.php?section=admin", true, 301);
                            exit;
                        }
                        
                    }
                }
            } else {
                foreach($errors as $error) {
                    echo $error."<br>";
                }
            }
        }

        //******************************** SELECT/RENDER
        $items = get_items();

        echo build_tree($items,0);
        $parents = build_tree_select($items,0,@$parent_id,$id);
        include_once "./tpl/admin.phtml";
    break;
}
?>