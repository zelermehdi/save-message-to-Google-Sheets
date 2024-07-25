<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class ContactController extends Controller
{
    public function showForm()
    {
        return view('contact');
    }

    public function submitForm(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required',
        ]);

        if (!session('google_access_token')) {
            return redirect()->route('google-auth')->with('error', 'Please authenticate with Google first.');
        }

        $this->saveToGoogleSheet($validated);

        session(['data' => $validated]);

        return back()->with('success', 'Message sent successfully!');
    }

    protected function saveToGoogleSheet($data)
    {
        $token = session('google_access_token');

        $client = new Google_Client();
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            session(['google_access_token' => $client->getAccessToken()]);
        }

        $service = new Google_Service_Sheets($client);
        $spreadsheetId = '15dT2AZR6bsSq0QBry2-YHlYPHNjdsx1q5jeYh_T10Aw';  
        $range = 'Feuille 1!A1:C1';  

        $values = [
            [$data['name'], $data['email'], $data['message']]
        ];

        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    }
}
