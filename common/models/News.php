<?php
namespace Models;

class News extends \Phalcon\Mvc\Model
{

	/**
	 * @var integer
	 *
	 */
	public $id;

	/**
	 * @var string
	 *
	 */
	public $title;

	/**
	 * @var string
	 *
	 */
	public $slug;

	/**
	 * @var string
	 *
	 */
	public $body;

	/**
	 * @var integer
	 *
	 */
	public $status;

	/**
	 * @var string
	 *
	 */
	public $create_time;

	/**
	 * @var string
	 *
	 */
	public $update_time;

	/**
	 * @var integer
	 *
	 */
	public $create_user_id;

	/**
	 * @var integer
	 *
	 */
	public $update_user_id;

	/**
	 * Initializer method for model.
	 */
	public function initialize()
	{
		$this->useDynamicUpdate(true);
		$this->addBehavior(new \Models\Behaviors\SlugBehavior(array(
			'slug_col' => 'slug', //The column name for the slug
			'title_col' => 'title', //The column name for the unqiue url
			'pk_col' => 'id', //Primary key
			'overwrite' => true, //Overwrite slug when updating
			'url_decode' => false //Decode url only usefull if you want to support high unicode characters in url
				)));
		$this->addBehavior(new \Models\Behaviors\CommentBehavior(array(
			'class' => '\Models\Behaviors\CommentBehavior',
			'class_name' => 'News',
			'owner_title' => 'title', // Attribute name to present comment owner in admin panel
      'pk' => 'id' //model's Primary key
			)));
		$this->belongsTo('create_user_id', '\Models\Users', 'id', array('alias' => 'Owner'));
		$this->belongsTo('update_user_id', '\Models\Users', 'id', array('alias' => 'Updater'));
	}

	public function validation()
	{
		$this->validate(new \Phalcon\Mvc\Model\Validator\PresenceOf(array('field' => 'body', 'message' =>
				'Содержание объявления обязательно для заполнения')));
		$this->validate(new \Phalcon\Mvc\Model\Validator\StringLength(array(
			"field" => "title",
			"max" => 255,
			"min" => 2,
			"maximumMessage" => "Слишком длинное заглавие")));
		return $this->validationHasFailed() != true;
	}

	public function beforeValidationOnCreate()
	{
		$created = $this->readAttribute('create_time');
		$updated = $this->readAttribute('update_time');
		if (null === $created)
		{
			$this->create_time = new \Phalcon\Db\RawValue('NOW()');
		}
		if (null === $updated)
		{
			$this->update_time = new \Phalcon\Db\RawValue('NOW()');
		}
	}

	public function beforeValidationOnUpdate()
	{
		$created = $this->readAttribute('create_time');
		if (null === $created)
		{
			$this->create_time = new \Phalcon\Db\RawValue('NOW()');
		}
		$this->update_time = new \Phalcon\Db\RawValue('NOW()');
	}

	public function afterDelete()
	{
		if (!$this->_dir)
		{
			$dir = 'public/uploads/news/images_id_' . $this->id;
			$itemImageFolder = __dir__ . '/../../' . $dir;
			\Helpers\CFileHelper::removeDirRec($itemImageFolder); //mkdir($this->_dir, 0777, true);
		} else
		{
			$itemImageFolder = __dir__ . '/../../' . $this->_dir;
			\Helpers\CFileHelper::removeDirRec($itemImageFolder);
		}
	}

	//директория с изображениями
	private $_dir;
 	/**
	 * Возвращает изображения 
	 * @return array массив, содержащий данные о изображениях вида
	 * array(array('name'=>'', 'path'=>'', 'thumb_name'=>'', 'thumb_path'=>''))
	 */
	public function getImages()
	{
		if (!$this->_dir)
		{
			$this->_dir = 'public/uploads/news/images_id_' . $this->id; //mkdir($this->_dir, 0777, true);
		}
		$itemImageFolder = __dir__ . '/../../' . $this->_dir;
		$result = array();
		if (is_dir($itemImageFolder))
		{
			$images = \Helpers\CFileHelper::findFiles($itemImageFolder, array(
				'fileTypes' => array(
					'jpg',
					'gif',
					'png'),
				'level' => 0,
				)); //Обрабатываем данные для получения приемлимого формата
			foreach ($images as $image)
			{
				$imageData['path'] = $image; //Получаем имя файла
				$nameData = explode('/', str_replace("\\", "/", $image));
				$imageData['name'] = $nameData[count($nameData) - 1];
				$result[] = $imageData;
			}
		}
		return $result;
	}

	public function deleteImage($name)
	{
		//только для сохраненного изображения
		if (!($this->getDirtyState() == \Phalcon\Mvc\Model::DIRTY_STATE_TRANSIENT))
		{
			if (!$this->_dir)
			{
				$this->_dir = 'public/uploads/news/images_id_' . $this->id; //mkdir($this->_dir, 0777, true);
			}
			//путь к файлу
			$itemImageFolder = __dir__ . '/../../' . $this->_dir;
			$filePath = $itemImageFolder . '/' . $name;
			if (is_file($filePath))
				unlink($filePath);
		}
	}

}
