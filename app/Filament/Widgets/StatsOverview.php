<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '2s'; // Optimized polling interval to avoid excessive database queries

    protected function getStats(): array
    {
        return [];
//        $totalProfit = Order::with('details.product') // Load order details with product relations
//        ->get()
//            ->sum(function ($order) {
//                return $order->details->sum(function ($orderDetail) {
//                    return ($orderDetail->product->net_price - $orderDetail->product->buy_price) * $orderDetail->quantity;
//                });
//            });
//        if (Auth::check() && Auth::user()->hasRole(['super_admin','admin'])) {
//            return [
//                // 🔹 USERS & ROLES
//                Stat::make('Total Roles', Role::count())
//                    ->color('primary')
//                    ->icon('heroicon-o-identification')
//                    ->description("Different user roles in the system"),
//
//                Stat::make('Admins', User::where('type', 'admin')->count())
//                    ->color('success')
//                    ->icon('heroicon-o-user-group')
//                    ->description("System administrators"),
//
//                Stat::make('Managers', User::where('type', 'manager')->count())
//                    ->color('info')
//                    ->icon('heroicon-o-briefcase')
//                    ->description("Business managers"),
//
//                Stat::make('Sales', User::where('type', 'sales')->count())
//                    ->color('warning')
//                    ->icon('heroicon-o-currency-dollar')
//                    ->description("Sales representatives"),
//
//                Stat::make('Delivery Personnel', User::where('type', 'courier')->count())
//                    ->color('danger')
//                    ->icon('heroicon-o-truck')
//                    ->description("Active delivery staff"),
//
//                // 🔹 LOCATIONS
//                Stat::make('Governorates', Governorate::count())
//                    ->color('primary')
//                    ->icon('heroicon-o-map')
//                    ->description("Number of governorates covered"),
//
//                Stat::make('Cities', City::count())
//                    ->color('primary')
//                    ->icon('heroicon-o-map-pin')
//                    ->description("Number of cities covered"),
//
//                // 🔹 CUSTOMERS & STORES
//                Stat::make('Total Customers', Customer::count())
//                    ->color('success')
//                    ->icon('heroicon-o-users')
//                    ->description("Registered customers"),
//
//                Stat::make('Total Stores', Store::count())
//                    ->color('warning')
//                    ->icon('heroicon-o-shopping-cart')
//                    ->description("Partner stores"),
//
//                // 🔹 PRODUCTS
//                Stat::make('Total Products', Product::count())
//                    ->color('success')
//                    ->icon('heroicon-o-archive-box')
//                    ->description("Available products"),
//
//                // 🔹 ORDERS
//                Stat::make('Total Orders', Order::count())
//                    ->color('info')
//                    ->icon('heroicon-o-clipboard-document-check')
//                    ->description("All processed orders"),
//
//                Stat::make('Open Orders', Order::whereIn('order_status', ['new', 'processing'])->count())
//                    ->color('danger')
//                    ->icon('heroicon-o-exclamation-circle')
//                    ->description("Pending and processing orders"),
//
//                Stat::make('Shipping Orders', Order::whereIn('order_status', ['shipped'])->count())
//                    ->color('info')
//                    ->icon('heroicon-o-truck')
//                    ->description("Orders in transit"),
//
//                Stat::make('Closed Orders', Order::whereIn('order_status', ['delivered', 'cancelled'])->count())
//                    ->color('success')
//                    ->icon('heroicon-o-check-circle')
//                    ->description("Delivered or cancelled orders"),
//
//                Stat::make('Total Orders Amounts', number_format(Order::sum('total_price'), 2) . ' $')
//                    ->color('primary')
//                    ->icon('heroicon-o-currency-dollar')
//                    ->description("Total Orders Amounts"),
//
//                // 🔹 NET PROFIT
//                Stat::make('Net Profit', number_format($totalProfit, 2) . ' $')
//                    ->color('success')
//                    ->icon('heroicon-o-banknotes')
//                    ->description("Total profit from all orders"),
//            ];
//        }elseif(Auth::check() && Auth::user()->hasRole('courier')){
//            return[
//                Stat::make('Total Orders', Order::where('courier_id',auth()->user()->id)->count())
//                    ->color('info')
//                    ->icon('heroicon-o-clipboard-document-check')
//                    ->description("All processed orders"),
//
//                Stat::make('Open Orders', Order::where('courier_id',auth()->user()->id)->whereIn('order_status', ['new', 'processing'])->count())
//                    ->color('danger')
//                    ->icon('heroicon-o-exclamation-circle')
//                    ->description("Pending and processing orders"),
//
//                Stat::make('Shipping Orders', Order::where('courier_id',auth()->user()->id)->whereIn('order_status', ['shipped'])->count())
//                    ->color('info')
//                    ->icon('heroicon-o-truck')
//                    ->description("Orders in transit"),
//
//                Stat::make('Closed Orders', Order::where('courier_id',auth()->user()->id)->whereIn('order_status', ['delivered', 'cancelled'])->count())
//                    ->color('success')
//                    ->icon('heroicon-o-check-circle')
//                    ->description("Delivered or cancelled orders"),
//            ];
//        }else{
//            return[
//                Stat::make('Total Orders', Order::count())
//                    ->color('info')
//                    ->icon('heroicon-o-clipboard-document-check')
//                    ->description("All processed orders"),
//
//                Stat::make('Open Orders', Order::whereIn('order_status', ['new', 'processing'])->count())
//                    ->color('danger')
//                    ->icon('heroicon-o-exclamation-circle')
//                    ->description("Pending and processing orders"),
//
//                Stat::make('Shipping Orders', Order::whereIn('order_status', ['shipped'])->count())
//                    ->color('info')
//                    ->icon('heroicon-o-truck')
//                    ->description("Orders in transit"),
//
//                Stat::make('Closed Orders', Order::whereIn('order_status', ['delivered', 'cancelled'])->count())
//                    ->color('success')
//                    ->icon('heroicon-o-check-circle')
//                    ->description("Delivered or cancelled orders"),
//            ];
//        }
    }
}
