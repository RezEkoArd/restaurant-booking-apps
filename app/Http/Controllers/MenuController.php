<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Menu::query();

            // filter by category if provided
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // search by name if provide
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            //Pagination
            $perPage = $request->get('per_page', 15);
            $menus = $query->orderBy('name', 'asc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Menu retrieved successfully',
                'data' => $menus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieved menus',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedDate = $request->validate([
                'name' => 'required|string|max:255|unique:menus,name',
                'price' => 'required|string|max:255',
                'category' => 'required|string|max:255',
            ]);

            $menu = Menu::create($validatedDate);

            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully',
                'data' => $menu
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $menu = Menu::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu retrieved successfully',
                'data' => $menu
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request,  $id): JsonResponse
    {
        try {
            $menu = Menu::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|string',
                'category' => 'sometimes|required|string|max:255',
            ]);

            $menu->update($request->only(['name', 'price', 'category']));

            return response()->json([
                'success' => true,
                'message' => 'Menu updated Successfully',
                'data' => $menu,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Menu',
                'errors' => $e->getMessage()
            ]);
        };
    }

    public function destroy($id)
    {
        try {
            $menu = Menu::findOrFail($id);
            $menu->delete();

            return response()->json([
                'success' => true,
                'message' => "Menu deleted Successfully"
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
