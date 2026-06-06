import { execFileSync } from 'node:child_process';

export type SeedContext = {
  userEmail: string;
  userPassword: string;
  accountId: string;
  secondAccountId: string;
};

const runPhp = (command: string): string => execFileSync('php', ['artisan', command], { encoding: 'utf8' }).trim();

export const seedBaseData = (): SeedContext => {
  runPhp('migrate:fresh --force');

  const json = runPhp("tinker --execute=\"$u=App\\Models\\User::create(['name'=>'E2E Admin','email'=>'e2e@example.com','password'=>bcrypt('password')]);$a=App\\Models\\Account::create(['user_id'=>$u->id,'username'=>'primary_account','instagram_id'=>'ig-primary','access_token'=>'token1','is_active'=>1]);$a2=App\\Models\\Account::create(['user_id'=>$u->id,'username'=>'secondary_account','instagram_id'=>'ig-secondary','access_token'=>'token2','is_active'=>1]);App\\Models\\BlockedAccount::create(['instagram_account_id'=>$a->id,'blocked_username'=>'seeded_troll','blocked_instagram_id'=>'999','reason'=>'Seed reason']);echo json_encode(['email'=>$u->email,'password'=>'password','account_id'=>$a->id,'account_id_2'=>$a2->id]);\"");

  let parsed: any;
  try {
    parsed = JSON.parse(json);
  } catch (error) {
    throw new Error(`Failed to parse seed data JSON: ${json}`);
  }

  const requiredKeys = ['email', 'password', 'account_id', 'account_id_2'];
  for (const key of requiredKeys) {
    if (!(key in parsed) || parsed[key] === null || parsed[key] === undefined) {
      throw new Error(`Seed data missing required key: ${key}. Received: ${JSON.stringify(parsed)}`);
    }
  }

  return {
    userEmail: parsed.email,
    userPassword: parsed.password,
    accountId: String(parsed.account_id),
    secondAccountId: String(parsed.account_id_2),
  };
};
