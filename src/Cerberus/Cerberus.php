<?php

/*
 * This file is part of CarePlan Manager by CircleLink Health.
 */

namespace Michalisantoniou6\Cerberus;

/**
 * This class is the main entry point of cerberus. Usually the interaction
 * with this class will be done through the Cerberus Facade.
 *
 * @license MIT
 */
class Cerberus
{
    /**
     * Laravel application.
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;

    /**
     * Create a new confide instance.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Check if the current user has a role or permission by its name.
     *
     * @param array|string $roles       the role(s) needed
     * @param array|string $permissions the permission(s) needed
     * @param array        $options     the Options
     *
     * @return bool
     */
    public function ability($roles, $permissions, $options = [])
    {
        if ($user = $this->user()) {
            return $user->ability($roles, $permissions, $options);
        }

        return false;
    }

    /**
     * Check if the current user has a permission by its name.
     *
     * @param string $permission permission string
     * @param mixed  $requireAll
     *
     * @return bool
     */
    public function hasPermission($permission, $requireAll = false)
    {
        if ($user = $this->user()) {
            return $user->hasPermission($permission, $requireAll);
        }

        return false;
    }

    /**
     * Checks if the current user has a role by its name.
     *
     * @param string $name       role name
     * @param mixed  $role
     * @param mixed  $requireAll
     *
     * @return bool
     */
    public function hasRole($role, $requireAll = false)
    {
        if ($user = $this->user()) {
            return $user->hasRole($role, $requireAll);
        }

        return false;
    }

    /**
     * Filters a route for a permission or set of permissions.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string       $route       Route pattern. i.e: "admin/*"
     * @param array|string $permissions The permission(s) needed
     * @param mixed        $result      i.e: Redirect::to('/')
     * @param bool         $requireAll  User must have all permissions
     *
     * @return mixed
     */
    public function routeNeedsPermission($route, $permissions, $result = null, $requireAll = true)
    {
        $filterName = is_array($permissions) ? implode('_', $permissions) : $permissions;
        $filterName .= '_'.substr(md5($route), 0, 6);

        $closure = function () use ($permissions, $result, $requireAll) {
            $hasPerm = $this->hasPermission($permissions, $requireAll);

            if ( ! $hasPerm) {
                return empty($result) ? $this->app->abort(403) : $result;
            }
        };

        // Same as Route::filter, registers a new filter
        $this->app->router->filter($filterName, $closure);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->app->router->when($route, $filterName);
    }

    /**
     * Filters a route for a role or set of roles.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string       $route      Route pattern. i.e: "admin/*"
     * @param array|string $roles      The role(s) needed
     * @param mixed        $result     i.e: Redirect::to('/')
     * @param bool         $requireAll User must have all roles
     *
     * @return mixed
     */
    public function routeNeedsRole($route, $roles, $result = null, $requireAll = true)
    {
        $filterName = is_array($roles) ? implode('_', $roles) : $roles;
        $filterName .= '_'.substr(md5($route), 0, 6);

        $closure = function () use ($roles, $result, $requireAll) {
            $hasRole = $this->hasRole($roles, $requireAll);

            if ( ! $hasRole) {
                return empty($result) ? $this->app->abort(403) : $result;
            }
        };

        // Same as Route::filter, registers a new filter
        $this->app->router->filter($filterName, $closure);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->app->router->when($route, $filterName);
    }

    /**
     * Filters a route for role(s) and/or permission(s).
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string       $route       Route pattern. i.e: "admin/*"
     * @param array|string $roles       The role(s) needed
     * @param array|string $permissions The permission(s) needed
     * @param mixed        $result      i.e: Redirect::to('/')
     * @param bool         $requireAll  User must have all roles and permissions
     *
     * @return void
     */
    public function routeNeedsRoleOrPermission($route, $roles, $permissions, $result = null, $requireAll = false)
    {
        $filterName = is_array($roles) ? implode('_', $roles) : $roles;
        $filterName .= '_'.(is_array($permissions) ? implode('_', $permissions) : $permissions);
        $filterName .= '_'.substr(md5($route), 0, 6);

        $closure = function () use ($roles, $permissions, $result, $requireAll) {
            $hasRole  = $this->hasRole($roles, $requireAll);
            $hasPerms = $this->hasPermission($permissions, $requireAll);

            if ($requireAll) {
                $hasRolePerm = $hasRole && $hasPerms;
            } else {
                $hasRolePerm = $hasRole || $hasPerms;
            }

            if ( ! $hasRolePerm) {
                return empty($result) ? $this->app->abort(403) : $result;
            }
        };

        // Same as Route::filter, registers a new filter
        $this->app->router->filter($filterName, $closure);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->app->router->when($route, $filterName);
    }

    /**
     * Get the currently authenticated user or null.
     *
     * @return Illuminate\Auth\UserInterface|null
     */
    public function user()
    {
        return $this->app->auth->user();
    }
}
