import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Zap } from 'lucide-react';
import { useFormContext } from 'react-hook-form';

interface UserFormProps {
    loading: boolean;
    userExists: boolean;
    errors: {
        name?: string[];
        email?: string[];
        password?: string[];
    };
    onSave: () => void;
}

export function UserForm({
    loading,
    userExists,
    errors,
    onSave,
}: UserFormProps) {
    const { register } = useFormContext();

    return (
        <div className="max-w-md space-y-4">
            <div className="space-y-2">
                <Label htmlFor="name">User Name</Label>
                <Input
                    id="name"
                    type="text"
                    {...register('name')}
                    placeholder="Enter user name"
                    disabled={loading}
                />
                {errors.name && (
                    <p className="text-sm text-destructive">{errors.name[0]}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="email">User Email</Label>
                <Input
                    id="email"
                    type="email"
                    {...register('email')}
                    placeholder="Enter user email"
                    disabled={loading}
                />
                {errors.email && (
                    <p className="text-sm text-destructive">
                        {errors.email[0]}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="password">
                    User Password{' '}
                    {!userExists && <span className="text-destructive">*</span>}
                </Label>
                <Input
                    id="password"
                    type="password"
                    {...register('password')}
                    placeholder={
                        userExists
                            ? 'Leave empty to keep current password'
                            : 'Enter password (required for new user)'
                    }
                    disabled={loading}
                />
                {errors.password && (
                    <p className="text-sm text-destructive">
                        {errors.password[0]}
                    </p>
                )}
            </div>

            <Button onClick={onSave} disabled={loading} className="w-full">
                {loading && <Zap className="mr-2 h-4 w-4 animate-spin" />}
                Save Changes
            </Button>
        </div>
    );
}
