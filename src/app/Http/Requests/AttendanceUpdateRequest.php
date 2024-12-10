<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'check_in' => ['required', 'date_format:H:i'],
            'check_out' => ['required', 'date_format:H:i', 'after:check_in'],
            'breaktimes.*.start' => ['nullable', 'date_format:H:i'],
            'breaktimes.*.end' => ['nullable', 'date_format:H:i', 'after:breaktimes.*.start'],
            'remarks' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * エラーメッセージのカスタマイズ
     */
    public function messages()
    {
        return [
            'check_in.required' => '出勤時間を入力してください',
            'check_in.date_format' => '出勤時間の形式が不正です',
            'check_out.required' => '退勤時間を入力してください',
            'check_out.date_format' => '退勤時間の形式が不正です',
            'check_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaktimes.*.start.date_format' => '休憩開始時間の形式が不正です',
            'breaktimes.*.end.date_format' => '休憩終了時間の形式が不正です',
            'breaktimes.*.end.after' => '休憩時間が勤務時間外です',
            'remarks.required' => '備考を記入してください',
            'remarks.max' => '備考は255文字以内で入力してください',
        ];
    }

    /**
     * バリデーション後の追加ロジック
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $checkIn = $this->input('check_in');
            $checkOut = $this->input('check_out');

            if ($checkIn && $checkOut) {
                foreach ($this->input('breaktimes', []) as $breaktime) {
                    $start = $breaktime['start'] ?? null;
                    $end = $breaktime['end'] ?? null;

                    // 休憩開始と終了が片方のみ入力されている場合
                    if (($start && !$end) || (!$start && $end)) {
                        $validator->errors()->add('breaktimes.*', '休憩開始時間と終了時間はセットで入力してください');
                    }

                    // 開始または終了が勤務時間外の場合
                    if ($start && ($start < $checkIn || $start > $checkOut)) {
                        $validator->errors()->add('breaktimes.*.start', '休憩時間が勤務時間外です');
                    }

                    if ($end && ($end < $checkIn || $end > $checkOut)) {
                        $validator->errors()->add('breaktimes.*.end', '休憩時間が勤務時間外です');
                    }
                }
            }
        });
    }
}
