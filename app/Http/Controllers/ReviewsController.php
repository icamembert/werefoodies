<?php namespace App\Http\Controllers;

use App\Order;
use App\Dish;
use App\Review;
use App\Http\Requests\ReviewRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Request;

class ReviewsController extends Controller {

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'update', 'destroy']]);
        //$this->middleware('dish.creator', ['only' => ['edit', 'update', 'destroy']]);
    }

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
        return view('reviews.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
     * @param ReviewRequest $request
	 * @return Response
	 */
	public function store(ReviewRequest $request)
	{
        $review = Auth::user()->reviews()->create($request->all());

        $order = Order::findOrFail($review->order_id);
        $order->status_id = 4;
        $order->save();

        foreach ($order->dishes as $dish)
        {
        	$nOrders = Order::select('orders.*')
        						->leftJoin('dish_order', 'orders.id', '=', 'dish_order.order_id')
        						->join('dishes', 'dish_order.dish_id', '=', 'dishes.id')
        						->where('dishes.id', '=', $dish->id)
        						->where('orders.status_id', '=', 4)
        						->groupBy('orders.id')
        						->count();
        	$dish->rating = ($dish->rating * ($nOrders - 1) + Input::get('dish_rating')) / $nOrders;
        	$dish->save();
        }

        /*$dish = Dish::findOrFail($order->dish_id);
        $nOrders = Order::where('dish_id', $dish->id)->where('status_id', '=', 4)->count();
        $dish->rating = ($dish->rating * ($nOrders - 1) + Input::get('dish_rating')) / $nOrders;
        $dish->save();*/

        $chef = $dish->user;
        $nReviews = $chef->clientReviews->count();
        $chef->rating = ($chef->rating * ($nReviews - 1) + Input::get('chef_rating')) / $nReviews;
        $chef->save();


        flash()->success('Your review has been posted!');

        return redirect()->intended('/my-account')->with('flash_message', 'Your review has been posted!');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Review  $review
	 * @return Response
	 */
	public function show(Review $review)
	{
        return view('reviews.show', compact('review'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  Review  $review
	 * @return Response
	 */
    public function edit(Review $review)
    {
        return view('reviews.edit', compact('review'));
    }

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  Review  $review
     * @param  ReviewRequest $request
	 * @return Response
	 */
	public function update(Review $review, ReviewRequest $request)
	{
        $review->update($request->all());

        flash()->success('Your review has been updated!');

        return redirect('reviews');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

}
