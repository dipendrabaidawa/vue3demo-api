<?php
namespace App\Repositories;

use App\Helpers\FileUploader;
use App\Interfaces\ApiCrudInterface;
use App\Http\Resources\ProductCollection;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductRepository implements ApiCrudInterface{

    public function all()
    {
        return Product::orderBy('id', 'asc')
	        ->with('user')
	        ->paginate(9);
    }

    public function myProducts()
    {
        return Product::orderBy('id', 'asc')
            ->with('user')
            ->where('user_id', auth()->guard()->user()->id)
            ->paginate(9);
    }


    public function paginate($perPage)
    {
        $perPage = isset($perPage) ? $perPage : 9;
        return Product::orderBy('id', 'asc')
	        ->with('user')
	        ->paginate($perPage);
    }


    public function search($keyword, $page, $user) {
        $perPage = isset($perPage) ? $perPage : 9;
        return Product::when($user != null, function ($q) use ($user) {
                return $q->where('user_id', $user);
            })
            ->where('title', 'like', '%'.$keyword.'%')
	        ->orWhere('description', 'like', '%'.$keyword.'%')
	        ->orWhere('price', 'like', '%'.$keyword.'%')
	        ->orderBy('id', 'asc')
	        ->with('user')
	        ->paginate($perPage, ['*'], 'page', $page);
    }


    public function create(array $data) {
        // $data['user_id'] = auth()->guard()->user()->id;
        $data['user_id'] = 1;

        // upload image file
        if(isset($data['image'])) {
            if($data['image'] != null && $data['image'] != '' && !is_string($data['image'])) {
                $data['image']   = FileUploader::store('image', $data['image'], $data['title'] ,'gallery/products');
            }
        }
        return Product::create($data);
    }


    public function find($id) {
        return Product::with('user')->find($id);
    }


    public function update($id, array $data) {
        $product = Product::find($id);
        if($product){
            if(isset($data['image'])){
                if($data['image'] != null && $data['image'] != '' && !is_string($data['image'])){
                    $data['image']   = FileUploader::update('image', $data['image'], $data['title'] ,'gallery/products', $product->image);
                }
            }
            # update product
            $product->update($data);

            return $this->find($product->id);
        }
    }


    public function delete($id) {
        $product = Product::find($id);

        if($product){
            FileUploader::delete('gallery/products/'.$product->image);
            $product->delete($product);

            return true;
        }
        return false;
    }
}