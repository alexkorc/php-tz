<?php
function auth_check($email, $password) {
    global $pdo;
    if(!$email || !$password) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND password = ?");
    $stmt->execute([$email, md5($password)]);
    if($stmt->rowCount()) {
        return true;
    } else {
        return false;
    }
}
function get_items() {
    global $pdo; // знаю, что это фу, но оопешные решения в таком задании оверинжениринг
    $stmt = $pdo->prepare("SELECT * FROM items");
    $stmt->execute();
    $items = [];
    if ($stmt->rowCount() > 0){
        while($item =  $stmt->fetch()){
            $items[$item['parent_id']][$item['id']] = $item;
        }
    }
    return $items;
}
function build_tree($items,$parent_id){
    if(is_array($items) and isset($items[$parent_id])){
        $tree = '<ul>';
        foreach($items[$parent_id] as $item){
            $tree .= '<li>'.$item['name'].' #'.$item['id'];
            if($item['description'])
                $tree .= '('.$item['description'].')';
            $tree .= ' <a href="index.php?section=admin&id='.$item['id'].'">upd</a> ';
            $tree .= ' <a href="#" name="d" id="d'.$item['id'].'">del</a>';
            $tree .=  build_tree($items,$item['id']);
            $tree .= '</li>';
        }
        $tree .= '</ul>';
    }
    else return null;
    return $tree;
}

function build_tree_index($items,$parent_id){
    if(is_array($items) and isset($items[$parent_id])){
        $tree = '<ul>';
        foreach($items[$parent_id] as $item){
            $display = "";
            if($parent_id != 0)
                $display = "display:none;";

            $tree .= '<li data-description = "'.$item['description'].'" data-parent-id = "p'.$item['parent_id'].'" id="l'.$item['id'].'" name="l" style='.$display.'>'.$item['name'].' #'.$item['id'];
            if(isset($items[$item['id']]))
                $tree .= ' <a href="#" name="i" id="i'.$item['id'].'">+</a>';
            $tree .=  build_tree_index($items,$item['id']);
            $tree .= '</li>';
        }
        $tree .= '</ul>';
    }
    else return null;
    return $tree;
}
// сформировать массив из всех потомков (дети детей включаются)
function get_children_ids_all($items, $parent_id, $childrens_id = []) {
    if(is_array($items) and isset($items[$parent_id])){
        foreach($items[$parent_id] as $item){
            $childrens_id[] = $item;
            if(count($r = get_children_ids_all($items,$item['id']))) {
                foreach($r as $item) {
                    $childrens_id[] = $item;
                }
            }
        }
    }
    else return [];
    return $childrens_id;
}
// сформировать массив из только детей (дети детей исключаются)
function get_children_ids($items, $parent_id, $childrens_id = []) {
    if(is_array($items) and isset($items[$parent_id])){
        foreach($items[$parent_id] as $item){
            $childrens_id[] = $item;
        }
    }
    else return [];
    return $childrens_id;
}

function build_tree_select($items,$parent_id,$selected_id,$disabled_id,$level=1){
    $parent_id = (int)$parent_id;
    $selected_id = (int)$selected_id;
    $disabled_id = (int)$disabled_id;
    if(is_array($items) and isset($items[$parent_id])){
        $tree = '';
        foreach($items[$parent_id] as $item){
            $selected = $selected_id === $item['id'] ? "selected" : "";
            $disabled = $disabled_id === $item['id'] ? "disabled" : "";

            $tree .= '<option '.$selected.' '.$disabled.' value="'.$item['id'].'">'.str_repeat("-", $level).$item['name'].'</option>';
            $tree .=  build_tree_select($items,$item['id'],$selected_id, $disabled_id, $level+1);
        }
    }
    else return null;
    return $tree;
}