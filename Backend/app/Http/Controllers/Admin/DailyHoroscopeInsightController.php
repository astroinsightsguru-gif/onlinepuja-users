<?php
namespace App\Http\Controllers\Admin;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\UserModel\HororscopeSign;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
if (!defined('LOGINPATH')) define('LOGINPATH', '/admin/login');

class DailyHoroscopeInsightController extends Controller
{
    public function getDailyHoroscopeInsight(Request $request)
    {
        try {
            if (Auth::guard('web')->check()) {
                $dailyHoroscopeInsight = DB::table('dailyhoroscopeinsight')
                    ->join('hororscope_signs', 'hororscope_signs.id', '=', 'dailyhoroscopeinsight.horoscopeSignId');
                $request->filterSign = $request->filterSign ? $request->filterSign : null;
                $request->filterDate = $request->filterDate ? $request->filterDate : null;
                if ($request->filterDate) {
                    $filterDate = Carbon::parse($request->filterDate)->format('Y-m-d');
                    $dailyHoroscopeInsight = $dailyHoroscopeInsight->where(DB::raw("(DATE_FORMAT(horoscopeDate,'%Y-%m-%d'))"), $filterDate);
                } else {
                    $filterDate = Carbon::now()->format('Y-m-d');
                    $dailyHoroscopeInsight = $dailyHoroscopeInsight->where(DB::raw("(DATE_FORMAT(horoscopeDate,'%Y-%m-%d'))"), $filterDate);
                }
                if ($request->filterSign) {
                    $dailyHoroscopeInsight = $dailyHoroscopeInsight->where('horoscopeSignId', '=', $request->filterSign);
                } else {
                    $dailyHoroscopeInsight = $dailyHoroscopeInsight->where('horoscopeSignId', '=', 1);
                }
                $dailyHoroscopeInsight = $dailyHoroscopeInsight->select('dailyhoroscopeinsight.*', 'hororscope_signs.name as signName')->orderBy('dailyhoroscopeinsight.horoscopeDate', 'DESC')->get();
                $hororscopeSign = HororscopeSign::query();
                $signs = $hororscopeSign->get();
                if ($request->filterSign) {
                    $selectedId = $request->filterSign;
                } else {
                    $selectedId = $signs[0]->id;
                }
                $filterDate = $request->filterDate ? Carbon::parse($request->filterDate)->format('Y-m-d') : Carbon::Now()->format('Y-m-d');
                return view('pages.daily-horoscope-insight', compact('dailyHoroscopeInsight', 'signs', 'selectedId', 'filterDate'));
            } else {
                return redirect(LOGINPATH);
            }
        } catch (Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function addDailyHoroscopeInsight(Request $req)
    {
        try {
            if (!Auth::guard('web')->check()) {
                return redirect(LOGINPATH);
            }

            // Insert basic info first
            $insightData = [
                'name' => $req->name,
                'title' => $req->title,
                'description' => $req->description,
                'horoscopeSignId' => $req->horoscopeSignId,
                'horoscopeDate' => $req->horoscopeDate,
                'link' => $req->link,
            ];

            DB::table('dailyhoroscopeinsight')->insert($insightData);
            $id = DB::getPdo()->lastInsertId();

            $path = null;

            // Handle image upload if provided
            if ($req->hasFile('coverImage')) {
                $file = $req->file('coverImage');
                $imageContent = file_get_contents($file->getRealPath());
                $extension = $file->getClientOriginalExtension();
                $imageName = 'dailyhoroscope_' . $id . '_' . time() . '.' . $extension;

                // Upload via StorageHelper
                $path = StorageHelper::uploadToActiveStorage($imageContent, $imageName, 'dailyhoroscope');
            }

            // Update coverImage in DB
            DB::table('dailyhoroscopeinsight')->where('id', $id)->update([
                'coverImage' => $path,
            ]);

            return redirect()
                ->route('dailyHoroscopeInsight')
                ->with('success', 'Daily Horoscope Insight added successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function editDailyHoroscopeInsight(Request $req)
    {
        try {
            if (!Auth::guard('web')->check()) {
                return redirect(LOGINPATH);
            }

            $insight = DB::table('dailyhoroscopeinsight')->where('id', $req->id)->first();

            if (!$insight) {
                return back()->with('error', 'Record not found!');
            }

            $path = $insight->coverImage;

            // If new image uploaded
            if ($req->hasFile('coverImage')) {
                $file = $req->file('coverImage');
                $imageContent = file_get_contents($file->getRealPath());
                $extension = $file->getClientOriginalExtension();
                $imageName = 'dailyhoroscope_' . $req->id . '_' . time() . '.' . $extension;

                // Delete old image if exists in storage
                if ($insight->coverImage && Storage::exists($insight->coverImage)) {
                    Storage::delete($insight->coverImage);
                }

                // Upload new image
                $path = StorageHelper::uploadToActiveStorage($imageContent, $imageName, 'dailyhoroscope');
            }

            // Update database record
            $updateData = [
                'name' => $req->name,
                'title' => $req->title,
                'description' => $req->editdescription,
                'horoscopeSignId' => $req->horoscopeSignId,
                'horoscopeDate' => $req->horoscopeDate,
                'link' => $req->link,
                'coverImage' => $path,
            ];

            DB::table('dailyhoroscopeinsight')
                ->where('id', $req->id)
                ->update($updateData);

            return redirect()
                ->route('dailyHoroscopeInsight')
                ->with('success', 'Daily Horoscope Insight updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function deleteHoroscopeInsight(Request $request)
    {
        try {
            if (Auth::guard('web')->check()) {
                DB::table('dailyhoroscopeinsight')->where('id', '=', $request->del_id)->delete();
                return redirect()->route('dailyHoroscopeInsight');
            } else {
                return redirect(LOGINPATH);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
