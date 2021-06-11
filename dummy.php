<?php
class mainCourse{
    public $breakfast;
    public $lunch;
    public $dinner;

    function set_breakfast($value){
        $this->breakfast = $value;
    }

    function set_lunch($lunch){
        $this->lunch = $lunch;
    }

    function set_dinner($myDinner){
        $this->dinner = $myDinner;
    }
}

//create an array that contains an array with an object that has an array in the key-value pair

$fruits = ['Apple', 'bananas', 'mango', 'melon'];

$other = ['Sweet potatos', 'onions', 'cabbages', 'cassava',  'minji'];

// $main_course = (
//     breakfast='tea',
//     lunch= 'Ugali',
//     dinner= $other
// )
$food = new mainCourse();
$food->set_dinner($other);

array_push($fruits, $food);

$main_array = ['Crisps', 'chips', 'burger'];
array_push($main_array, $fruits);
print_r($main_array);